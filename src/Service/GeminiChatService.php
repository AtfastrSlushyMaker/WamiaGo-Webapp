<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class GeminiChatService
{
    private $client;
    private $apiKey;

    public function __construct(HttpClientInterface $client, string $apiKey)
    {
        $this->client = $client;
        $this->apiKey = $apiKey;
    }

    public function generateFeedbackSuggestion(string $title = null, string $category = null): string
    {
        $prompt = "Generate a concise, professional reclamation message";
        
        if ($title) {
            $prompt .= " about: \"$title\"";
        }
        
        if ($category) {
            $prompt .= " in category: \"$category\"";
        }
        
        $prompt .= ". Include a greeting, brief problem description, requested resolution, and polite closing. Keep it under 150 words, professional in tone, and ready to submit.";

        // Updated to use gemini-2.0-flash model
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=' . $this->apiKey;

        $response = $this->client->request('POST', $url, [
            'json' => [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'temperature' => 0.7,
                    'topK' => 40,
                    'topP' => 0.95,
                    'maxOutputTokens' => 250
                ]
            ]
        ]);

        $data = $response->toArray();

        // Return the response from Gemini API
        return $data['candidates'][0]['content']['parts'][0]['text'] ?? 'Unable to generate feedback suggestion at this time.';
    }

    public function enhanceUserText(string $text, string $title = null): string
    {
        $prompt = "Improve and organize the following text for a reclamation";
        
        if ($title) {
            $prompt .= " about \"$title\"";
        }
        
        $prompt .= ". Fix spelling and grammar, improve clarity and professionalism, but maintain the original meaning and intent. The text to improve is: \"$text\"";

        // Using gemini-2.0-flash model
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=' . $this->apiKey;

        $response = $this->client->request('POST', $url, [
            'json' => [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'temperature' => 0.3, // Lower temperature for more precise editing
                    'topK' => 40,
                    'topP' => 0.95,
                    'maxOutputTokens' => 300
                ]
            ]
        ]);

        $data = $response->toArray();

        // Return the enhanced text
        return $data['candidates'][0]['content']['parts'][0]['text'] ?? 'Unable to enhance text at this time.';
    }
}