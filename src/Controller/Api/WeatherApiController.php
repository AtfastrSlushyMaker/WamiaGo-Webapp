<?php

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

#[Route('/api/weather')]
class WeatherApiController extends AbstractController
{
    private $params;

    public function __construct(ParameterBagInterface $params)
    {
        $this->params = $params;
    }

    #[Route('/key', name: 'app_api_weather_key')]
    public function getApiKey(): JsonResponse
    {
        // Get the API key from environment variables
        $apiKey = $this->params->get('app.openweather_api_key');

        return new JsonResponse(['apiKey' => $apiKey]);
    }
}