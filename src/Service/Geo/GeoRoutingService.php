<?php

namespace App\Service\Geo;

use Geocoder\Provider\Nominatim\Nominatim;
use Geocoder\Query\GeocodeQuery;
use Geocoder\Query\ReverseQuery;
use Geocoder\StatefulGeocoder;
use Http\Client\HttpClient;
use Http\Discovery\HttpClientDiscovery;
use Http\Discovery\Psr17FactoryDiscovery;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Log\LoggerInterface;

/**
 * GeoRoutingService provides enhanced routing capabilities for the bicycle rental system.
 * 
 * Features:
 * - Real-world distance calculation using OpenStreetMap routing
 * - Address geocoding and reverse geocoding
 * - Points of interest discovery near routes
 */
class GeoRoutingService
{
    private $geocoder;
    private $statefulGeocoder;
    private LoggerInterface $logger;
    private string $userAgent;
    private string $referer;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->userAgent = 'WamiaGo Bicycle App';
        $this->referer = $_SERVER['HTTP_HOST'] ?? 'wamiagobikes.com';

        $httpClient = HttpClientDiscovery::find();

        $requestFactory = Psr17FactoryDiscovery::findRequestFactory();
        $streamFactory = Psr17FactoryDiscovery::findStreamFactory();

        $this->geocoder = new Nominatim(
            $httpClient,
            $this->userAgent,
            $this->referer
        );
        
        // Create a stateful geocoder with caching capability
        $this->statefulGeocoder = new StatefulGeocoder($this->geocoder, 'en');
    }

    /**
     * Calculate the real-world cycling route distance between coordinates
     */
    public function calculateRouteDistance(float $startLat, float $startLon, float $endLat, float $endLon): array
    {
        try {
            
            $osrmUrl = sprintf(
                'http://router.project-osrm.org/route/v1/cycling/%f,%f;%f,%f?overview=false',
                $startLon, $startLat, $endLon, $endLat
            );
            
            $this->logger->info('Calling OSRM API for routing', [
                'url' => $osrmUrl,
                'start' => [$startLat, $startLon],
                'end' => [$endLat, $endLon]
            ]);
   
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $osrmUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_USERAGENT, $this->userAgent);
            $response = curl_exec($ch);
            
            if (curl_errno($ch)) {
                throw new \Exception('CURL Error: ' . curl_error($ch));
            }
            
            curl_close($ch);
            
            // Parse response
            $data = json_decode($response, true);
            
            if (!$data || $data['code'] !== 'Ok' || empty($data['routes'])) {
                throw new \Exception('Invalid or empty routing response');
            }
            
            // Extract distance and duration
            $distanceInMeters = $data['routes'][0]['distance'];
            $durationInSeconds = $data['routes'][0]['duration'];
            
            // Convert to km and minutes
            $distanceInKm = round($distanceInMeters / 1000, 2);
            $durationInMinutes = round($durationInSeconds / 60);
            
            $this->logger->info('Route calculation successful', [
                'distance_km' => $distanceInKm,
                'duration_min' => $durationInMinutes
            ]);
            
     
            return [
                'distance' => $distanceInKm,
                'duration' => $durationInMinutes,
                'route_type' => 'cycling',
                'provider' => 'OSRM',
                'success' => true
            ];
            
        } catch (\Exception $e) {
            $this->logger->error('Route calculation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
     
            $haversineDistance = $this->calculateHaversineDistance($startLat, $startLon, $endLat, $endLon);
            $estimatedDuration = $this->estimateCyclingDuration($haversineDistance);
            
            $this->logger->info('Using Haversine fallback', [
                'distance_km' => $haversineDistance,
                'duration_min' => $estimatedDuration
            ]);
            
            return [
                'distance' => $haversineDistance,
                'duration' => $estimatedDuration,
                'route_type' => 'straight-line',
                'provider' => 'Haversine',
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Calculate straight-line distance using Haversine formula (fallback method)
     */
    public function calculateHaversineDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {

        $earthRadius = 6371; // in kilometers
        
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        
        $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon/2) * sin($dLon/2);
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        $distance = $earthRadius * $c;
        
        return round($distance, 2);
    }
    
    /**
     * Estimate cycling duration based on distance and terrain
     */
    public function estimateCyclingDuration(float $distanceKm, string $terrain = 'mixed'): int
    {
        $speeds = [
            'flat' => 18, 
            'mixed' => 15, 
            'hilly' => 12  
        ];
        
  
        $speed = $speeds[$terrain] ?? $speeds['mixed'];
        
        $baseTime = 5; 
        
        $durationMinutes = max($baseTime, round(($distanceKm / $speed) * 60));
        
        return $durationMinutes;
    }
    
    /**
     * Find the nearest address to coordinates
     */
    public function reverseGeocode(float $latitude, float $longitude): ?string
    {
        try {
            $result = $this->statefulGeocoder->reverseQuery(ReverseQuery::fromCoordinates($latitude, $longitude));
            
            if (!$result->isEmpty()) {
                return $result->first()->getFormattedAddress();
            }
            
            return null;
        } catch (\Exception $e) {
            $this->logger->error('Reverse geocoding failed', [
                'error' => $e->getMessage(),
                'coordinates' => [$latitude, $longitude]
            ]);
            return null;
        }
    }
    
    /**
     * Get coordinates for an address
     */
    public function geocodeAddress(string $address): ?array
    {
        try {
            $result = $this->statefulGeocoder->geocodeQuery(GeocodeQuery::create($address));
            
            if (!$result->isEmpty()) {
                $coordinates = $result->first()->getCoordinates();
                return [
                    'latitude' => $coordinates->getLatitude(),
                    'longitude' => $coordinates->getLongitude()
                ];
            }
            
            return null;
        } catch (\Exception $e) {
            $this->logger->error('Geocoding failed', [
                'error' => $e->getMessage(),
                'address' => $address
            ]);
            return null;
        }
    }
    
    /**
     * Find nearby points of interest
     */
    public function findNearbyPointsOfInterest(float $latitude, float $longitude, float $radiusKm = 1.0, string $type = ''): array
    {
        try {
            $radius = $radiusKm * 1000; 
            

            $typeQuery = '';
            if (!empty($type)) {
                $typeQuery = "[\"amenity\"=\"$type\"]";
            }
            
            $query = "[out:json];
                (
                  node$typeQuery(around:$radius,$latitude,$longitude);
                );
                out body;";
            
        
            $encodedQuery = urlencode($query);
            
  
            $url = "https://overpass-api.de/api/interpreter?data=$encodedQuery";
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_USERAGENT, $this->userAgent);
            $response = curl_exec($ch);
            
            if (curl_errno($ch)) {
                throw new \Exception('CURL Error: ' . curl_error($ch));
            }
            
            curl_close($ch);
            
    
            $data = json_decode($response, true);
            
            if (!$data || !isset($data['elements'])) {
                return [];
            }
            
         
            $pois = [];
            foreach ($data['elements'] as $element) {
                if (isset($element['tags'])) {
                    $poi = [
                        'id' => $element['id'],
                        'type' => $element['tags']['amenity'] ?? 'unknown',
                        'name' => $element['tags']['name'] ?? 'Unnamed',
                        'latitude' => $element['lat'],
                        'longitude' => $element['lon'],
                        'address' => $element['tags']['addr:street'] ?? null,
                    ];
                    
                    $pois[] = $poi;
                }
            }
            
            return $pois;
        } catch (\Exception $e) {
            $this->logger->error('POI search failed', [
                'error' => $e->getMessage(),
                'location' => [$latitude, $longitude]
            ]);
            
            return [];
        }
    }
    
    /**
     * Calculate the elevation gain between two points
     */
    public function getElevationData(float $startLat, float $startLon, float $endLat, float $endLon): array
    {
        try {
         
            $url = sprintf(
                'https://api.open-elevation.com/api/v1/lookup?locations=%f,%f|%f,%f',
                $startLat, $startLon, $endLat, $endLon
            );
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_USERAGENT, $this->userAgent);
            $response = curl_exec($ch);
            
            if (curl_errno($ch)) {
                throw new \Exception('CURL Error: ' . curl_error($ch));
            }
            
            curl_close($ch);
            
   
            $data = json_decode($response, true);
            
            if (!$data || !isset($data['results']) || count($data['results']) < 2) {
                throw new \Exception('Invalid elevation data response');
            }

            $startElevation = $data['results'][0]['elevation'];
            $endElevation = $data['results'][1]['elevation'];
            $elevationDiff = $endElevation - $startElevation;
            
  
            $routeType = 'flat';
            if ($elevationDiff > 10) {
                $routeType = 'uphill';
            } elseif ($elevationDiff < -10) {
                $routeType = 'downhill';
            }
            
            return [
                'start_elevation' => $startElevation,
                'end_elevation' => $endElevation,
                'elevation_difference' => $elevationDiff,
                'route_type' => $routeType
            ];
        } catch (\Exception $e) {
            $this->logger->error('Elevation data fetch failed', [
                'error' => $e->getMessage()
            ]);
            
            return [
                'start_elevation' => 0,
                'end_elevation' => 0,
                'elevation_difference' => 0,
                'route_type' => 'unknown'
            ];
        }
    }
}