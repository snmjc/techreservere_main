<?php

namespace App\Domain\PendingUser\Controller;

use App\Shared\Traits\JsonResponseTrait;
use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/pending-users')]
class PendingUserController
{
    use JsonResponseTrait;

    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    #[Route('', name: 'pending_users_list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        try {
            $status = $request->query->get('status');

            $sql = 'SELECT * FROM pending_users';
            $params = [];

            if ($status) {
                $sql .= ' WHERE status = :status';
                $params['status'] = $status;
            }

            $sql .= ' ORDER BY created_at DESC';

            $results = $this->connection->fetchAllAssociative($sql, $params);

            return $this->createSuccessResponse($results);
        } catch (\Exception $e) {
            return $this->createErrorResponse('FetchError', $e->getMessage(), 500);
        }
    }

    #[Route('', name: 'pending_users_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        try {
            $body = json_decode($request->getContent(), true) ?? [];

            $email = trim($body['email'] ?? '');
            $fullName = trim($body['fullName'] ?? '');
            $department = trim($body['department'] ?? '');
            $organization = trim($body['organization'] ?? '');
            $phone = trim($body['phone'] ?? '');

            if (empty($email) || empty($fullName)) {
                // 422 Unprocessable Entity is commonly used for semantic validation errors.
                return $this->createErrorResponse('ValidationError', 'Email and full name are required.', 422);
            }

            // Check if email already exists
            $existing = $this->connection->fetchAssociative(
                'SELECT id FROM pending_users WHERE email = :email',
                ['email' => $email]
            );

            if ($existing) {
                return $this->createErrorResponse('DuplicateEmail', 'A registration with this email already exists.', 409);
            }

            $id = $this->generateUuid();
            $now = (new \DateTimeImmutable())->format('Y-m-d\TH:i:sP');

            $this->connection->insert('pending_users', [
                'id' => $id,
                'email' => $email,
                'full_name' => $fullName,
                'department' => $department ?: null,
                'organization' => $organization ?: null,
                'phone' => $phone ?: null,
                'status' => 'pending',
                'created_at' => $now,
            ]);

            $record = $this->connection->fetchAssociative(
                'SELECT * FROM pending_users WHERE id = :id',
                ['id' => $id]
            );

            return $this->createSuccessResponse($record, 201);
        } catch (\Exception $e) {
            return $this->createErrorResponse('CreateError', $e->getMessage(), 500);
        }
    }

    #[Route('/{id}/approve', name: 'pending_users_approve', methods: ['PUT', 'PATCH'])]
    public function approve(string $id): JsonResponse
    {
        try {
            $now = (new \DateTimeImmutable())->format('Y-m-d\TH:i:sP');

            $affected = $this->connection->update('pending_users', [
                'status' => 'approved',
                'approved_at' => $now,
            ], ['id' => $id]);

            if ($affected === 0) {
                return $this->createErrorResponse('NotFound', 'Pending user not found.', 404);
            }

            $record = $this->connection->fetchAssociative(
                'SELECT * FROM pending_users WHERE id = :id',
                ['id' => $id]
            );

            return $this->createSuccessResponse($record);
        } catch (\Exception $e) {
            return $this->createErrorResponse('ApproveError', $e->getMessage(), 500);
        }
    }

    #[Route('/{id}/reject', name: 'pending_users_reject', methods: ['PUT', 'PATCH'])]
    public function reject(string $id, Request $request): JsonResponse
    {
        try {
            $body = json_decode($request->getContent(), true) ?? [];
            $reason = trim($body['reason'] ?? '');
            $now = (new \DateTimeImmutable())->format('Y-m-d\TH:i:sP');

            $affected = $this->connection->update('pending_users', [
                'status' => 'rejected',
                'rejection_reason' => $reason ?: null,
                'rejected_at' => $now,
            ], ['id' => $id]);

            if ($affected === 0) {
                return $this->createErrorResponse('NotFound', 'Pending user not found.', 404);
            }

            $record = $this->connection->fetchAssociative(
                'SELECT * FROM pending_users WHERE id = :id',
                ['id' => $id]
            );

            return $this->createSuccessResponse($record);
        } catch (\Exception $e) {
            return $this->createErrorResponse('RejectError', $e->getMessage(), 500);
        }
    }

    private function generateUuid(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }
}
