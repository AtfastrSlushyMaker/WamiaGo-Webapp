<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Exception;

class TrafficTimeEstimator
{
    private string $apiKey;
    private string $geocodeUrl = 'https://geocode.search.hereapi.com/v1/geocode';
    private string $routingUrl = 'https://router.hereapi.com/v8/routes';
    private HttpClientInterface $httpClient;

    // Tunisia extreme coordinates (lat, lng)
    private const TUNISIA_BBOX = [
        [32.2295, 7.5248],   // Southwest (Kebili)
        [37.3452, 11.5983]   // Northeast (Bizerte)
    ];

    public function __construct(HttpClientInterface $httpClient, string $apiKey)
    {
        $this->httpClient = $httpClient;
        $this->apiKey = $apiKey;
    }

    private function sendGetRequest(string $url): array
    {
        $response = $this->httpClient->request('GET', $url);

        if ($response->getStatusCode() !== 200) {
            throw new Exception('HttpResponseCode: ' . $response->getStatusCode());
        }

        return $response->toArray();
    }

    public function getCoordinates(string $city): array
    {
        $encodedCity = urlencode($city);
        $url = "{$this->geocodeUrl}?q={$encodedCity}&apiKey={$this->apiKey}";

        $response = $this->sendGetRequest($url);

        if (!isset($response['items']) || empty($response['items'])) {
            throw new Exception("City not found: {$city}");
        }

        $position = $response['items'][0]['position'];

        return [
            $position['lat'],
            $position['lng']
        ];
    }

    public function calculateTravelTime(string $originCity, string $destinationCity): array
    {
        $origin = $this->getCoordinates($originCity);
        $dest = $this->getCoordinates($destinationCity);

        $url = "{$this->routingUrl}?transportMode=car" .
            "&origin={$origin[0]},{$origin[1]}" .
            "&destination={$dest[0]},{$dest[1]}" .
            "&return=summary" .
            "&apiKey={$this->apiKey}";

        $response = $this->sendGetRequest($url);
        return $this->parseTravelTime($response);
    }

    private function parseTravelTime(array $response): array
    {
        if (!isset($response['routes']) || empty($response['routes'])) {
            throw new Exception("No routes found in response");
        }

        $sections = $response['routes'][0]['sections'] ?? null;

        if (empty($sections)) {
            throw new Exception("No sections in route");
        }

        $summary = $sections[0]['summary'] ?? null;

        if (!isset($summary['duration'])) {
            throw new Exception("Duration not found in response");
        }

        $duration = $summary['duration'];

        // Parse duration in seconds
        if (is_numeric($duration)) {
            $hours = floor($duration / 3600);
            $minutes = floor(($duration % 3600) / 60);
            $formattedTime = sprintf("%02d:%02d", $hours, $minutes);

            return [
                'duration_seconds' => $duration,
                'formatted_time' => $formattedTime,
                'hours' => $hours,
                'minutes' => $minutes
            ];
        }

        // For ISO duration format (if API returns that format)
        return [
            'duration_raw' => $duration,
            'formatted_time' => $duration
        ];
    }
}