<?php

namespace App\Controller\Api;

use App\Service\Speech\AzureSpeechService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api')]
class SpeechController extends AbstractController
{
    #[Route('/speech-token', name: 'api_speech_token', methods: ['GET'])]
    public function getSpeechToken(AzureSpeechService $speechService): JsonResponse
    {
        try {
            $tokenData = $speechService->getSpeechToken();
            return $this->json($tokenData);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }

    #[Route('/speech/config', name: 'api_speech_config', methods: ['GET'])]
    public function getConfig(AzureSpeechService $speechService): JsonResponse
    {
        try {
            return $this->json($speechService->getConfig());
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }
}