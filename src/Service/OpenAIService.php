<?php

namespace App\Service;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class OpenAIService
{
    private HttpClientInterface $client;
    private string $apiKey;

    public function __construct(string $apiKey)
    {
        $this->client = HttpClient::create();
        $this->apiKey = $apiKey;
    }

    public function generateText(string $prompt, string $language = 'auto'): string
{
    // Détection automatique de la langue si 'auto'
    if ($language === 'auto') {
        $language = $this->detectLanguage($prompt);
    }

    // Adaptation du prompt en fonction de la langue
    $languagePrefix = $language === 'fr' 
        ? "Génère un texte pour une annonce de transport professionnelle en français. "
        : "Generate a professional transport announcement text in English. ";

    try {
        $response = $this->client->request('POST', 'https://api.openai.com/v1/completions', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'model' => 'gpt-3.5-turbo-instruct',
                'prompt' => $languagePrefix . $prompt,
                'max_tokens' => 200,
                'temperature' => 0.7,
            ],
        ]);

        $content = $response->toArray();
        return trim($content['choices'][0]['text']);
    } catch (ExceptionInterface $e) {
        throw new \RuntimeException('OpenAI API request failed: ' . $e->getMessage());
    }
}

private function detectLanguage(string $text): string
{
    // Détection simple basée sur des caractères spécifiques
    $frenchChars = ['é', 'è', 'ê', 'à', 'ù', 'ç'];
    $hasFrenchChars = count(array_intersect(str_split($text), $frenchChars)) > 0;

    // Si le texte contient des caractères français typiques
    if ($hasFrenchChars) {
        return 'fr';
    }

    // Par défaut en anglais
    return 'en';
}

public function generateTitleSuggestions(string $content, string $language = 'auto'): array
{
    try {
        $prompt = $language === 'fr'
            ? "Génère 3 titres accrocheurs pour une annonce de transport basée sur ce contenu. Format JSON: {\"titles\":[\"titre 1\", \"titre 2\"]}"
            : "Generate 3 catchy transport announcement titles based on this content. JSON format: {\"titles\":[\"title 1\", \"title 2\"]}";

        $response = $this->client->request('POST', 'https://api.openai.com/v1/chat/completions', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    ['role' => 'system', 'content' => $prompt],
                    ['role' => 'user', 'content' => $content]
                ],
                'response_format' => ['type' => 'json_object'],
                'max_tokens' => 100
            ]
        ]);

        $data = $response->toArray();
        return json_decode($data['choices'][0]['message']['content'], true)['titles'];

    } catch (\Exception $e) {
        throw new \RuntimeException('Title generation failed: ' . $e->getMessage());
    }
}
}