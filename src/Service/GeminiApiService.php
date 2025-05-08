<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class GeminiApiService
{
    private $httpClient;
    private $apiKey;
    // Update to the latest endpoint format for Gemini 2.0 Flash
    private $apiEndpoint = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent';

    public function __construct(HttpClientInterface $httpClient, string $apiKey)
    {
        $this->httpClient = $httpClient;
        $this->apiKey = $apiKey;
    }

    public function generateContent(string $prompt): array
    {
        try {
            $response = $this->httpClient->request('POST', $this->apiEndpoint . '?key=' . $this->apiKey, [
                'json' => [
                    'contents' => [
                        [
                            'role' => 'user',
                            'parts' => [
                                ['text' => $prompt]
                            ]
                        ]
                    ],
                    'generationConfig' => [
                        'temperature' => 0.2,
                        'maxOutputTokens' => 1000
                    ]
                ],
                'headers' => [
                    'Content-Type' => 'application/json'
                ]
            ]);

            // Check if response is successful
            if ($response->getStatusCode() >= 400) {
                return [
                    'error' => 'API Error: ' . $response->getStatusCode() . ' ' . $response->getContent(false)
                ];
            }

            return $response->toArray();
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
}