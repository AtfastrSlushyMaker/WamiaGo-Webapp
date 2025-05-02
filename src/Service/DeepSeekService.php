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
        string $apiKey,
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
                ],
                'json' => [
                    'model' => 'deepseek-chat',
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'Tu es un assistant qui prédit les prix de trajets. Réponds uniquement avec un nombre décimal représentant le prix.'
                        ],
                        [
                            'role' => 'user',
                            'content' => $prompt
                        ]
                    ],
                ],
                'timeout' => $this->timeout,
            ]);

            $statusCode = $response->getStatusCode();
            if ($statusCode !== 200) {
                $this->logger->error('DeepSeek API returned an error', [
                    'statusCode' => $statusCode,
                    'response' => $response->getContent(false),
                ]);
                return null;
            }

            $content = $response->toArray();
            $price = $content['choices'][0]['message']['content'] ?? null;

            if (is_numeric($price)) {
                $this->logger->info('Successfully retrieved price from DeepSeek', ['price' => $price]);
                return round((float) $price, 2);
            }

            $this->logger->warning('Invalid price format received from DeepSeek', ['response' => $content]);
            return null;
        } catch (\Exception $e) {
            $this->logger->error('Error during DeepSeek API call', [
                'exception' => $e->getMessage(),
            ]);
            return null;
        }
    }
}