<?php

namespace App\Domain\ReleaseReturn\Controller;

use App\Domain\ReleaseReturn\Service\ReleaseReturnProcessService;
use App\Shared\Traits\JsonResponseTrait;
use App\Shared\Utils\RequiresRoles;
use App\Shared\Utils\RoleConstants;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/release-returns')]
class ReleaseReturnController extends AbstractController
{
    use JsonResponseTrait;

    private ReleaseReturnProcessService $releaseReturnProcessService;

    public function __construct(ReleaseReturnProcessService $releaseReturnProcessService)
    {
        $this->releaseReturnProcessService = $releaseReturnProcessService;
    }

    #[Route('', name: 'release_return_process', methods: ['POST'])]
    #[RequiresRoles([RoleConstants::ROLE_ADMIN, RoleConstants::ROLE_DEVELOPER])]
    public function processTransaction(Request $request): JsonResponse
    {
        $body = json_decode($request->getContent(), true) ?? [];
        $dto = $this->releaseReturnProcessService->processReleaseReturn(
            reservationIdentifier: (int)($body['reservationIdentifier'] ?? 0),
            transactionType: $body['transactionType'] ?? '',
            equipmentItemList: $body['equipmentItemList'] ?? [],
            quantityProcessed: (int)($body['quantityProcessed'] ?? 0),
            conditionStatus: $body['conditionStatus'] ?? 'Good',
            remarksDescription: $body['remarksDescription'] ?? null,
            processedByAccountId: isset($body['processedByAccountId']) ? (int)$body['processedByAccountId'] : null
        );
        return $this->createSuccessResponse($dto->toResponseArray(), 201);
    }

    #[Route('/reservation/{reservationIdentifier}', name: 'release_return_by_reservation', methods: ['GET'])]
    #[RequiresRoles([RoleConstants::ROLE_ADMIN, RoleConstants::ROLE_DEVELOPER])]
    public function getByReservation(int $reservationIdentifier): JsonResponse
    {
        $dtos = $this->releaseReturnProcessService->getByReservation($reservationIdentifier);
        $responseList = array_map(fn($dto) => $dto->toResponseArray(), $dtos);
        return $this->createSuccessResponse(['releaseReturns' => $responseList]);
    }
}
