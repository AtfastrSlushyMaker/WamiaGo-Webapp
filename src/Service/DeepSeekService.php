<?php

namespace App\Service;

use App\Entity\Trip;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

class DeepSeekService
{
    private string $apiUrl;
    private HttpClientInterface $httpClient;
    private LoggerInterface $logger;
    public string $apiKey;
    private int $timeout;

    public function __construct(
        HttpClientInterface $httpClient,
        LoggerInterface $logger,
        string $apiKey= "sk-856cd7686cd94dfca0570b605d453812 ",
        string $apiUrl = 'https://api.deepseek.com/v1/chat/completions',
        int $timeout = 10
    ) {
        $this->httpClient = $httpClient;
        $this->logger = $logger;
        $this->apiKey = $apiKey;
        $this->apiUrl = $apiUrl;
        $this->timeout = $timeout;
    }
    public function predictPrice(Trip $trip): ?float
{
    if (empty($this->apiKey)) {
        $this->logger->error('DeepSeek API key is missing.');
        return null;
    }

    $departureCity = $trip->getDepartureCity();
    $arrivalCity = $trip->getArrivalCity();
    $availableSeats = $trip->getAvailableSeats();

    $this->logger->info('Sending price prediction request to DeepSeek', [
        'departure' => $departureCity,
        'arrival' => $arrivalCity,
        'seats' => $availableSeats,
    ]);

    $prompt = "Estime un prix pour un trajet entre $departureCity et $arrivalCity avec $availableSeats places disponibles. " .
        "Considère la distance et la demande pour donner un prix précis.";

    try {
        $response = $this->httpClient->request('POST', $this->apiUrl, [
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Accept' => 'application/json',
            ],
            'json' => [
                'model' => 'deepseek-chat',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are a price prediction assistant. Respond only with a decimal number representing the estimated price.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'temperature' => 0.7,
                'max_tokens' => 10,
            ],
            'timeout' => $this->timeout,
        ]);

        $statusCode = $response->getStatusCode();
        $content = $response->toArray(false);

        if ($statusCode === 503) {
            $this->logger->error('DeepSeek API is unavailable', [
                'status' => $statusCode,
                'response' => $content,
            ]);
            throw new \RuntimeException('The prediction service is temporarily unavailable');
        }

        if ($statusCode === 429) {
            $this->logger->error('Rate limit exceeded for DeepSeek API');
            throw new \RuntimeException('Too many requests. Please try again later');
        }

        if ($statusCode !== 200) {
            $this->logger->error('DeepSeek API error', [
                'status' => $statusCode,
                'response' => $content,
            ]);
            throw new \RuntimeException('Prediction service returned an error');
        }

        if (isset($content['choices'][0]['message']['content'])) {
            $price = (float) $content['choices'][0]['message']['content'];
            return $price;
        } else {
            $this->logger->error('Invalid response format from DeepSeek API', [
                'response' => $content,
            ]);
            return null;
        }
    } catch (\Exception $e) {
        $this->logger->error('DeepSeek API call failed', [
            'exception' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);
        return null;
    }
}




}