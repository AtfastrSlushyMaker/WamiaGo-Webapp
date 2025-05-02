<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Exception;

class CityFinder
{
    private const API_URL = 'https://ipinfo.io/json';
    private HttpClientInterface $httpClient;

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    /**
     * Get the current city based on IP address
     *
     * @return string The detected city name
     * @throws Exception If the API request fails
     */
    public function getCurrentCity(): string
    {
        $response = $this->makeApiRequest();

        if (!isset($response['city'])) {
            throw new Exception('City information not found in API response');
        }

        return $response['city'];
    }

    /**
     * Get the geographical coordinates based on IP address
     *
     * @return array Array containing latitude and longitude
     * @throws Exception If the API request fails
     */
    public function getCoordinates(): array
    {
        $response = $this->makeApiRequest();

        if (!isset($response['loc'])) {
            throw new Exception('Location coordinates not found in API response');
        }

        $loc = explode(',', $response['loc']);

        if (count($loc) !== 2) {
            throw new Exception('Invalid location format in API response');
        }

        return [
            'latitude' => (float) $loc[0],
            'longitude' => (float) $loc[1]
        ];
    }

    /**
     * Makes the API request and returns the parsed JSON response
     *
     * @return array The API response as an associative array
     * @throws Exception If the request fails
     */
    private function makeApiRequest(): array
    {
        try {
            $response = $this->httpClient->request('GET', self::API_URL, [
                'headers' => [
                    'Accept' => 'application/json'
                ]
            ]);

            if ($response->getStatusCode() !== 200) {
                throw new Exception('API request failed with status code: ' . $response->getStatusCode());
            }

            return $response->toArray();
        } catch (Exception $e) {
            throw new Exception('Failed to retrieve location data: ' . $e->getMessage());
        }
    }
}