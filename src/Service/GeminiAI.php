<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class GeminiAI
{
    private LoggerInterface $logger;
    private string $apiKey;
    private string $model;
    private int $timeout;

    public function __construct(
        LoggerInterface $logger,
        string $apiKey,
        string $model,
        int $timeout
    ) {
        $this->logger = $logger;
        $this->apiKey = $apiKey;
        $this->model = $model;
        $this->timeout = $timeout;
    }

    /**
     * Send a prompt to Gemini API and get the response
     *
     * @param string $prompt The text prompt to send
     * @param array $options Additional options for the API request
     * @return array|null The parsed response or null on failure
     */
    public function generateContent(string $prompt, array $options = []): ?array
    {
        try {
            $url = "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent?key={$this->apiKey}";

            $data = [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt]
                        ]
                    ]
                ]
            ];

            // Add any additional options
            if (!empty($options)) {
                $data = array_merge($data, $options);
            }

            // Initialize cURL session
            $ch = curl_init($url);

            // Set cURL options
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);

            // Execute cURL request
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_errno($ch) ? curl_error($ch) : null;

            // Close cURL session
            curl_close($ch);

            // Log request info
            $this->logger->info('Gemini API request completed', [
                'httpCode' => $httpCode,
                'responseLength' => strlen($response),
                'hasError' => !empty($curlError)
            ]);

            // Check for cURL errors
            if ($curlError) {
                $this->logger->error('Gemini API cURL error', [
                    'error' => $curlError
                ]);
                return null;
            }

            // Check if request was successful
            if ($httpCode !== 200 || empty($response)) {
                $this->logger->warning('Gemini API request failed', [
                    'httpCode' => $httpCode,
                    'response' => $response
                ]);
                return null;
            }

            // Parse response
            $responseData = json_decode($response, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->logger->error('Failed to parse Gemini API response', [
                    'jsonError' => json_last_error_msg()
                ]);
                return null;
            }

            return $responseData;
        } catch (\Exception $e) {
            $this->logger->error('Error calling Gemini API', [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * Extract a numeric value from Gemini response
     *
     * @param array|null $response The Gemini API response
     * @param float $min Minimum acceptable value
     * @param float $max Maximum acceptable value
     * @return float|null The extracted numeric value or null if not found or invalid
     */
    public function extractNumber(?array $response, float $min = 0, float $max = 1000): ?float
    {
        if (!$response || !isset($response['candidates'][0]['content']['parts'][0]['text'])) {
            return null;
        }

        $text = $response['candidates'][0]['content']['parts'][0]['text'];

        // Extract number from text response using regex
        if (preg_match('/([0-9]+\.?[0-9]*)/', $text, $matches)) {
            $number = (float)$matches[1];

            // Validate the number is within acceptable range
            if ($number >= $min && $number <= $max) {
                return $number;
            } else {
                $this->logger->warning('Extracted number outside acceptable range', [
                    'number' => $number,
                    'min' => $min,
                    'max' => $max
                ]);
            }
        } else {
            $this->logger->warning('Could not extract number from Gemini response', [
                'text' => $text
            ]);
        }

        return null;
    }
}