<?php

namespace App\Controller;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class HealthController extends AbstractController
{
    #[Route('/health', name: 'health_index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $data = [
            'status' => 'ok',
            'time' => (new \DateTime())->format(\DateTime::ATOM),
        ];

        return new JsonResponse($data, 200, ['Access-Control-Allow-Origin' => '*']);
    }

    #[Route('/health/db', name: 'health_db', methods: ['GET'])]
    public function db(Connection $connection): JsonResponse
    {
        try {
            // execute a lightweight query to verify DB connectivity
            $result = $connection->executeQuery('SELECT 1')->fetchOne();
            $connected = ($result !== false && $result !== null);

            $data = [
                'db' => 'ok',
                'connected' => $connected,
            ];

            return new JsonResponse($data, 200, ['Access-Control-Allow-Origin' => '*']);
        } catch (\Throwable $e) {
            return new JsonResponse(
                ['db' => 'error', 'message' => $e->getMessage()],
                503,
                ['Access-Control-Allow-Origin' => '*']
            );
        }
    }
}
