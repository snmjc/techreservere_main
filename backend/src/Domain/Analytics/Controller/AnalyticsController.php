<?php

declare(strict_types=1);

namespace App\Domain\Analytics\Controller;

use App\Shared\Traits\JsonResponseTrait;
use App\Shared\Traits\RequestPayloadTrait;
use App\Shared\Utils\RequiresRoles;
use App\Shared\Utils\RoleConstants;
use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[Route('/api/v1/analytics')]
class AnalyticsController
{
    use JsonResponseTrait;
    use RequestPayloadTrait;

    public function __construct(
        private readonly Connection $connection,
        private readonly HttpClientInterface $httpClient
    ) {
    }

    #[Route('/configuration', name: 'analytics_get_configuration', methods: ['GET'])]
    #[RequiresRoles([RoleConstants::ROLE_ADMIN, RoleConstants::ROLE_DEVELOPER])]
    public function getConfiguration(): JsonResponse
    {
        try {
            $configuration = $this->connection->fetchAssociative(
                'SELECT config_key, config_payload, is_active, created_at, updated_at
                   FROM analytics_configurations
                  WHERE config_key = :configKey
                    AND is_active = TRUE
                  ORDER BY updated_at DESC
                  LIMIT 1',
                ['configKey' => 'daily_analytics']
            );

            return $this->createSuccessResponse([
                'configuration' => $configuration ? $this->normalizeRow($configuration) : $this->defaultConfiguration(),
            ]);
        } catch (\Throwable $exception) {
            return $this->createErrorResponse('AnalyticsConfigurationFetchFailed', $exception->getMessage(), 500);
        }
    }

    #[Route('/configuration', name: 'analytics_save_configuration', methods: ['POST', 'PUT', 'PATCH'])]
    #[RequiresRoles([RoleConstants::ROLE_ADMIN, RoleConstants::ROLE_DEVELOPER])]
    public function saveConfiguration(Request $request): JsonResponse
    {
        try {
            $payload = $this->jsonBody($request);
            $configuration = is_array($payload['configuration'] ?? null) ? $payload['configuration'] : $payload;

            if (!is_array($configuration) || $configuration === []) {
                return $this->createErrorResponse('ValidationError', 'Analytics configuration payload is required.', 422);
            }

            $now = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
            $existing = $this->connection->fetchAssociative(
                'SELECT analytics_configuration_identifier
                   FROM analytics_configurations
                  WHERE config_key = :configKey
                  ORDER BY updated_at DESC
                  LIMIT 1',
                ['configKey' => 'daily_analytics']
            );

            if ($existing) {
                $this->connection->update(
                    'analytics_configurations',
                    [
                        'config_payload' => json_encode($configuration, JSON_THROW_ON_ERROR),
                        'updated_at' => $now,
                    ],
                    ['analytics_configuration_identifier' => $existing['analytics_configuration_identifier']]
                );
            } else {
                $this->connection->insert('analytics_configurations', [
                    'config_key' => 'daily_analytics',
                    'config_payload' => json_encode($configuration, JSON_THROW_ON_ERROR),
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            return $this->createSuccessResponse([
                'configuration' => $this->getStoredConfiguration(),
            ], 200);
        } catch (\Throwable $exception) {
            return $this->createErrorResponse('AnalyticsConfigurationSaveFailed', $exception->getMessage(), 500);
        }
    }

    #[Route('/latest-results', name: 'analytics_latest_results', methods: ['GET'])]
    #[RequiresRoles([RoleConstants::ROLE_ADMIN, RoleConstants::ROLE_DEVELOPER])]
    public function getLatestResults(): JsonResponse
    {
        try {
            $latestRun = $this->connection->fetchAssociative(
                'SELECT run_identifier, run_type, status, triggered_by, config_snapshot, summary_payload, error_message, started_at, completed_at
                   FROM analytics_runs
                  ORDER BY started_at DESC, run_identifier DESC
                  LIMIT 1'
            );

            if (!$latestRun) {
                return $this->createSuccessResponse([
                    'run' => null,
                    'results' => [],
                ]);
            }

            $results = $this->connection->fetchAllAssociative(
                'SELECT result_type, model_name, result_payload, generated_at
                   FROM analytics_results
                  WHERE run_identifier = :runIdentifier
                  ORDER BY generated_at DESC, result_type DESC',
                ['runIdentifier' => $latestRun['run_identifier']]
            );

            return $this->createSuccessResponse([
                'run' => $this->normalizeRow($latestRun),
                'results' => array_map(fn (array $row): array => $this->normalizeRow($row), $results),
            ]);
        } catch (\Throwable $exception) {
            return $this->createErrorResponse('AnalyticsResultsFetchFailed', $exception->getMessage(), 500);
        }
    }

    #[Route('/trigger-run', name: 'analytics_trigger_run', methods: ['POST'])]
    #[RequiresRoles([RoleConstants::ROLE_ADMIN, RoleConstants::ROLE_DEVELOPER])]
    public function triggerRun(Request $request): JsonResponse
    {
        try {
            $requestBody = $this->jsonBody($request);
            $scenario = strtolower(trim((string) ($requestBody['scenario'] ?? '')));
            $historyDays = max(1, (int) ($requestBody['historyDays'] ?? 30));
            $startDate = trim((string) ($requestBody['startDate'] ?? ''));
            $endDate = trim((string) ($requestBody['endDate'] ?? ''));
            error_log(sprintf('Analytics trigger requested with scenario: %s', $scenario !== '' ? $scenario : '(empty)'));
            $analyticsServiceUrl = rtrim((string) ($_ENV['ANALYTICS_SERVICE_URL'] ?? getenv('ANALYTICS_SERVICE_URL') ?: 'http://analytics-service:9000'), '/');
            $payload = $this->triggerAnalyticsService($analyticsServiceUrl, $scenario, $historyDays, $startDate, $endDate);
            $seededCount = is_array($payload['results']['forecast']['actualSeries'] ?? null)
                ? count($payload['results']['forecast']['actualSeries'])
                : null;
            error_log(sprintf('Analytics trigger completed with forecast points: %s', $seededCount === null ? 'n/a' : (string) $seededCount));

            return $this->createSuccessResponse([
                'analyticsServiceResponse' => $payload,
            ]);
        } catch (\Throwable $exception) {
            return $this->createErrorResponse('AnalyticsTriggerFailed', $exception->getMessage(), 502);
        }
    }

    #[Route('/model-artifacts', name: 'analytics_model_artifacts', methods: ['GET'])]
    #[RequiresRoles([RoleConstants::ROLE_ADMIN, RoleConstants::ROLE_DEVELOPER])]
    public function listModelArtifacts(): JsonResponse
    {
        try {
            $payload = $this->requestAnalyticsService('GET', '/analytics/model-artifacts');

            return $this->createSuccessResponse([
                'modelArtifacts' => $payload,
            ]);
        } catch (\Throwable $exception) {
            return $this->createErrorResponse('AnalyticsModelArtifactsFetchFailed', $exception->getMessage(), 502);
        }
    }

    #[Route('/train-models', name: 'analytics_train_models', methods: ['POST'])]
    #[RequiresRoles([RoleConstants::ROLE_ADMIN, RoleConstants::ROLE_DEVELOPER])]
    public function trainModels(Request $request): JsonResponse
    {
        try {
            $requestBody = $this->jsonBody($request);
            $payload = $this->requestAnalyticsService('POST', '/analytics/train-models', [
                'setName' => trim((string) ($requestBody['setName'] ?? '')),
                'activate' => (bool) ($requestBody['activate'] ?? true),
            ], 90);

            return $this->createSuccessResponse([
                'trainingRun' => $payload,
            ]);
        } catch (\Throwable $exception) {
            return $this->createErrorResponse('AnalyticsModelTrainingFailed', $exception->getMessage(), 502);
        }
    }

    #[Route('/model-artifacts/activate', name: 'analytics_activate_model_artifact', methods: ['POST'])]
    #[RequiresRoles([RoleConstants::ROLE_ADMIN, RoleConstants::ROLE_DEVELOPER])]
    public function activateModelArtifact(Request $request): JsonResponse
    {
        try {
            $requestBody = $this->jsonBody($request);
            $setName = trim((string) ($requestBody['setName'] ?? ''));
            if ($setName === '') {
                return $this->createErrorResponse('ValidationError', 'setName is required.', 422);
            }

            $payload = $this->requestAnalyticsService('POST', '/analytics/model-artifacts/activate', [
                'setName' => $setName,
            ]);

            return $this->createSuccessResponse([
                'activeModelSet' => $payload,
            ]);
        } catch (\Throwable $exception) {
            return $this->createErrorResponse('AnalyticsModelActivationFailed', $exception->getMessage(), 502);
        }
    }

    #[Route('/model-artifacts/activate-artifact', name: 'analytics_activate_single_model_artifact', methods: ['POST'])]
    #[RequiresRoles([RoleConstants::ROLE_ADMIN, RoleConstants::ROLE_DEVELOPER])]
    public function activateSingleModelArtifact(Request $request): JsonResponse
    {
        try {
            $requestBody = $this->jsonBody($request);
            $setName = trim((string) ($requestBody['setName'] ?? ''));
            $artifact = trim((string) ($requestBody['artifact'] ?? ''));
            if ($setName === '' || $artifact === '') {
                return $this->createErrorResponse('ValidationError', 'setName and artifact are required.', 422);
            }

            $payload = $this->requestAnalyticsService('POST', '/analytics/model-artifacts/activate-artifact', [
                'setName' => $setName,
                'artifact' => $artifact,
            ]);

            return $this->createSuccessResponse([
                'activeModelArtifacts' => $payload,
            ]);
        } catch (\Throwable $exception) {
            return $this->createErrorResponse('AnalyticsModelArtifactActivationFailed', $exception->getMessage(), 502);
        }
    }

    #[Route('/model-artifacts/{setName}', name: 'analytics_rename_model_artifact', methods: ['PATCH'])]
    #[RequiresRoles([RoleConstants::ROLE_ADMIN, RoleConstants::ROLE_DEVELOPER])]
    public function renameModelArtifact(string $setName, Request $request): JsonResponse
    {
        try {
            $requestBody = $this->jsonBody($request);
            $newName = trim((string) ($requestBody['newName'] ?? ''));
            if ($newName === '') {
                return $this->createErrorResponse('ValidationError', 'newName is required.', 422);
            }

            $payload = $this->requestAnalyticsService(
                'PATCH',
                '/analytics/model-artifacts/' . rawurlencode(trim($setName)),
                ['newName' => $newName]
            );

            return $this->createSuccessResponse([
                'renamedModelSet' => $payload,
            ]);
        } catch (\Throwable $exception) {
            return $this->createErrorResponse('AnalyticsModelRenameFailed', $exception->getMessage(), 502);
        }
    }

    #[Route('/model-artifacts/{setName}', name: 'analytics_delete_model_artifact', methods: ['DELETE'])]
    #[RequiresRoles([RoleConstants::ROLE_ADMIN, RoleConstants::ROLE_DEVELOPER])]
    public function deleteModelArtifact(string $setName): JsonResponse
    {
        try {
            $normalizedSetName = trim($setName);
            if ($normalizedSetName === '') {
                return $this->createErrorResponse('ValidationError', 'setName is required.', 422);
            }

            $payload = $this->requestAnalyticsService(
                'DELETE',
                '/analytics/model-artifacts/' . rawurlencode($normalizedSetName)
            );

            return $this->createSuccessResponse([
                'deletedModelSet' => $payload,
            ]);
        } catch (\Throwable $exception) {
            return $this->createErrorResponse('AnalyticsModelDeleteFailed', $exception->getMessage(), 502);
        }
    }

    private function getStoredConfiguration(): array
    {
        $configuration = $this->connection->fetchAssociative(
            'SELECT config_key, config_payload, is_active, created_at, updated_at
               FROM analytics_configurations
              WHERE config_key = :configKey
                AND is_active = TRUE
              ORDER BY updated_at DESC
              LIMIT 1',
            ['configKey' => 'daily_analytics']
        );

        return $configuration ? $this->normalizeRow($configuration) : $this->defaultConfiguration();
    }

    #[Route('/range-results', name: 'analytics_range_results', methods: ['GET'])]
    #[RequiresRoles([RoleConstants::ROLE_ADMIN, RoleConstants::ROLE_DEVELOPER])]
    public function getRangeResults(Request $request): JsonResponse
    {
        try {
            $historyDays = max(1, (int) $request->query->get('historyDays', 30));
            $startDate = trim((string) $request->query->get('startDate', ''));
            $endDate = trim((string) $request->query->get('endDate', ''));

            if ($startDate === '' || $endDate === '') {
                return $this->createErrorResponse('ValidationError', 'startDate and endDate are required.', 422);
            }

            $analyticsServiceUrl = rtrim((string) ($_ENV['ANALYTICS_SERVICE_URL'] ?? getenv('ANALYTICS_SERVICE_URL') ?: 'http://analytics-service:9000'), '/');
            $payload = $this->requestRangeAnalysis($analyticsServiceUrl, $historyDays, $startDate, $endDate);

            return $this->createSuccessResponse([
                'analyticsServiceResponse' => $payload,
            ]);
        } catch (\Throwable $exception) {
            return $this->createErrorResponse('AnalyticsRangeFetchFailed', $exception->getMessage(), 502);
        }
    }

    #[Route('/range-results/{section}', name: 'analytics_range_section_results', methods: ['GET'])]
    #[RequiresRoles([RoleConstants::ROLE_ADMIN, RoleConstants::ROLE_DEVELOPER])]
    public function getRangeSectionResults(string $section, Request $request): JsonResponse
    {
        try {
            $historyDays = max(1, (int) $request->query->get('historyDays', 30));
            $startDate = trim((string) $request->query->get('startDate', ''));
            $endDate = trim((string) $request->query->get('endDate', ''));
            $sectionName = strtolower(trim($section));

            if ($startDate === '' || $endDate === '') {
                return $this->createErrorResponse('ValidationError', 'startDate and endDate are required.', 422);
            }

            if (!in_array($sectionName, ['forecast', 'readiness', 'allocation'], true)) {
                return $this->createErrorResponse('ValidationError', 'Unsupported analytics section.', 422);
            }

            $analyticsServiceUrl = rtrim((string) ($_ENV['ANALYTICS_SERVICE_URL'] ?? getenv('ANALYTICS_SERVICE_URL') ?: 'http://analytics-service:9000'), '/');
            $payload = $this->requestRangeAnalysis($analyticsServiceUrl, $historyDays, $startDate, $endDate, $sectionName);

            return $this->createSuccessResponse([
                'analyticsServiceResponse' => $payload,
            ]);
        } catch (\Throwable $exception) {
            return $this->createErrorResponse('AnalyticsRangeSectionFetchFailed', $exception->getMessage(), 502);
        }
    }

    private function triggerAnalyticsService(
        string $analyticsServiceUrl,
        string $scenario,
        int $historyDays,
        string $startDate,
        string $endDate
    ): array
    {
        $lastException = null;

        for ($attempt = 1; $attempt <= 3; $attempt++) {
            try {
                $response = $this->httpClient->request('POST', $analyticsServiceUrl . '/analytics/run-daily-check', [
                    'json' => [
                        'scenario' => $scenario,
                        'historyDays' => $historyDays,
                        'startDate' => $startDate,
                        'endDate' => $endDate,
                    ],
                    'timeout' => 60,
                ]);

                return $response->toArray(false);
            } catch (\Throwable $exception) {
                $lastException = $exception;

                if ($attempt < 3) {
                    usleep(500000);
                }
            }
        }

        throw $lastException ?? new \RuntimeException('Analytics service request failed.');
    }

    private function requestRangeAnalysis(
        string $analyticsServiceUrl,
        int $historyDays,
        string $startDate,
        string $endDate,
        ?string $section = null
    ): array {
        $path = '/analytics/analyze-range' . ($section !== null ? '/' . rawurlencode($section) : '');
        $response = $this->httpClient->request('POST', $analyticsServiceUrl . $path, [
            'json' => [
                'historyDays' => $historyDays,
                'startDate' => $startDate,
                'endDate' => $endDate,
            ],
            'timeout' => 60,
        ]);

        return $response->toArray(false);
    }

    private function requestAnalyticsService(
        string $method,
        string $path,
        array $payload = [],
        int $timeout = 60
    ): array {
        $analyticsServiceUrl = rtrim((string) ($_ENV['ANALYTICS_SERVICE_URL'] ?? getenv('ANALYTICS_SERVICE_URL') ?: 'http://analytics-service:9000'), '/');
        $options = ['timeout' => $timeout];
        if ($payload !== []) {
            $options['json'] = $payload;
        }

        $response = $this->httpClient->request($method, $analyticsServiceUrl . $path, $options);

        return $response->toArray(false);
    }

    private function defaultConfiguration(): array
    {
        return [
            'config_key' => 'daily_analytics',
            'config_payload' => [
                'forecast' => [
                    'enabled' => true,
                    'model' => 'sarima',
                    'historyDays' => 180,
                    'forecastDays' => 3,
                    'seasonalPeriod' => 7,
                ],
                'readiness' => [
                    'enabled' => true,
                    'model' => 'random_forest',
                    'riskThreshold' => 0.65,
                ],
                'allocation' => [
                    'enabled' => true,
                    'model' => 'bilp',
                    'objective' => 'maximize_fulfillment',
                ],
            ],
            'is_active' => true,
            'created_at' => null,
            'updated_at' => null,
        ];
    }

    private function normalizeRow(array $row): array
    {
        foreach (['config_payload', 'summary_payload', 'result_payload'] as $jsonColumn) {
            if (!array_key_exists($jsonColumn, $row)) {
                continue;
            }

            $value = $row[$jsonColumn];
            if (is_string($value) && $value !== '') {
                $decoded = json_decode($value, true);
                $row[$jsonColumn] = is_array($decoded) ? $decoded : $value;
            }
        }

        return $row;
    }
}
