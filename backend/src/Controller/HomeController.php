<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'home_index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        return new JsonResponse([
            'service' => 'techreserve_backend',
            'status' => 'ok',
            'health' => '/health',
            'healthDb' => '/health/db',
        ], 200, ['Access-Control-Allow-Origin' => '*']);
    }

    #[Route('/favicon.ico', name: 'home_favicon', methods: ['GET'])]
    public function favicon(): Response
    {
        return new Response('', 204);
    }
}
