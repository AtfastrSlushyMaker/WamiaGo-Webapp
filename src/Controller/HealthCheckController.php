<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class HealthCheckController extends AbstractController
{
    #[Route('/health-check', name: 'app_health_check')]
    public function healthCheck(): JsonResponse
    {
        // Simple health check endpoint that doesn't require authentication
        return new JsonResponse([
            'status' => 'ok',
            'timestamp' => new \DateTime(),
            'message' => 'Server is running normally'
        ]);
    }
} 