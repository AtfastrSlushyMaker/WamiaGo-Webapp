<?php

namespace App\Controller;

use App\Service\GeminiChatService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class ChatController extends AbstractController
{
    #[Route('/feedback/generate', name: 'app_feedback_generate', methods: ['POST'])]
    public function generateFeedback(Request $request, GeminiChatService $geminiChatService): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $title = $data['title'] ?? null;
        $textToEnhance = $data['text'] ?? null;
        
        try {
            // If text is provided, enhance it instead of generating from scratch
            if ($textToEnhance) {
                $suggestion = $geminiChatService->enhanceUserText($textToEnhance, $title);
            } else {
                $suggestion = $geminiChatService->generateFeedbackSuggestion($title);
            }
            
            return new JsonResponse(['suggestion' => $suggestion]);
        } catch (\Exception $e) {
            return new JsonResponse(
                ['error' => 'Failed to generate feedback: ' . $e->getMessage()], 
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}