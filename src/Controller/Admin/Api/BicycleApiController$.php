<?php

namespace App\Controller\Admin\Api;

use App\Entity\BicycleStation;
use App\Entity\Location;
use App\Enum\BICYCLE_STATION_STATUS;
use App\Service\BicycleStationService;
use App\Service\LocationService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;

//#[IsGranted('ROLE_ADMIN')]
#[Route('/admin/bicycle/api')]
class BicycleApiController extends AbstractController
{
    private $entityManager;
    private $stationService;
    private $locationService;
    private $logger;

    public function __construct(
        EntityManagerInterface $entityManager,
        BicycleStationService $stationService,
        LocationService $locationService,
        LoggerInterface $logger
    ) {
        $this->entityManager = $entityManager;
        $this->stationService = $stationService;
        $this->locationService = $locationService;
        $this->logger = $logger;
    }

    #[Route('/bicycle-stations', name: 'api_bicycle_stations', methods: ['GET'])]
    public function getApiStations(): JsonResponse
    {
        try {
            $stations = $this->stationService->getAllStations();

            $data = [];
            foreach ($stations as $station) {
                // Make sure we have a location object
                if ($station->getLocation()) {
                    $data[] = [
                        'id' => $station->getIdStation(),
                        'name' => $station->getName(),
                        'location' => [
                            'latitude' => (float)$station->getLocation()->getLatitude(),
                            'longitude' => (float)$station->getLocation()->getLongitude(),
                            'address' => $station->getLocation()->getAddress()
                        ],
                        'availableBikes' => (int)$station->getAvailableBikes(),
                        'availableDocks' => (int)$station->getAvailableDocks(),
                        'totalDocks' => (int)$station->getTotalDocks(),
                        'status' => $station->getStatus()->value
                    ];
                }
            }

            return new JsonResponse($data);
        } catch (\Exception $e) {
            $this->logger->error('Error getting API stations: ' . $e->getMessage());
            return new JsonResponse(['error' => 'Failed to retrieve stations'], 500);
        }
    }

    #[Route('/api/stations', name: 'api_create_station', methods: ['POST'])]
#[Route('/api/bicycle-stations', name: 'api_create_bicycle_station', methods: ['POST'])]
    public function createStation(Request $request): JsonResponse
    {
        try {
            // Decode JSON data
            $data = json_decode($request->getContent(), true);
            
            // Log received data for debugging
            $this->logger->info('Received station creation request', $data ?? []);
            
            // Create a new station
            $station = new BicycleStation();
            $station->setName($data['name']);
            $station->setTotalDocks((int)$data['totalDocks']);
            $station->setAvailableBikes((int)$data['availableBikes']);
            $station->setAvailableDocks((int)$data['totalDocks'] - (int)$data['availableBikes']);
            $station->setChargingBikes(0); // Initialize charging bikes
            
            // Set status
            $station->setStatus(BICYCLE_STATION_STATUS::fromValue($data['status']));
                
            // Handle location
            $latitude = $data['latitude'] ?? null;
            $longitude = $data['longitude'] ?? null;
            $address = $data['address'] ?? null;
            $locationId = $data['locationId'] ?? null;
                
            if ($locationId) {
                // Use existing location
                $location = $this->locationService->findLocationById((int)$locationId);
                if (!$location) {
                    return new JsonResponse([
                        'success' => false,
                        'message' => 'Location not found'
                    ], 404);
                }
                $station->setLocation($location);
            } else if ($latitude && $longitude) {
                // Create new location
                $location = new Location();
                $location->setLatitude((float)$latitude);
                $location->setLongitude((float)$longitude);
                $location->setAddress($address ?: 'Unknown location');
                
                $this->entityManager->persist($location);
                $station->setLocation($location);
            } else {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Location data is required'
                ], 400);
            }
                
            // Save the station
            $this->entityManager->persist($station);
            $this->entityManager->flush();
                
            return new JsonResponse([
                'success' => true,
                'stationId' => $station->getIdStation(),
                'message' => 'Station created successfully'
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Error creating station: ' . $e->getMessage(), [
                'exception' => $e
            ]);
                
            return new JsonResponse([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    #[Route('/locations', name: 'api_locations', methods: ['GET'])]
    public function getLocations(): JsonResponse
    {
        try {
            $locations = $this->locationService->getAllLocations();
            
            $data = [];
            foreach ($locations as $location) {
                $data[] = [
                    'id' => $location->getIdLocation(),
                    'address' => $location->getAddress(),
                    'latitude' => (float)$location->getLatitude(),
                    'longitude' => (float)$location->getLongitude()
                ];
            }
            
            return new JsonResponse($data);
        } catch (\Exception $e) {
            $this->logger->error('Error getting locations: ' . $e->getMessage());
            return new JsonResponse(['error' => 'Failed to retrieve locations'], 500);
        }
    }
    #[Route('/api/debug', name: 'api_debug', methods: ['GET'])]
public function debugInfo(): JsonResponse
{
    $phpInfo = [
        'php_version' => phpversion(),
        'extensions' => get_loaded_extensions(),
        'memory_limit' => ini_get('memory_limit'),
        'max_execution_time' => ini_get('max_execution_time'),
        'post_max_size' => ini_get('post_max_size'),
        'upload_max_filesize' => ini_get('upload_max_filesize'),
    ];
    
    return new JsonResponse([
        'server_info' => $_SERVER,
        'php_info' => $phpInfo,
        'doctrine_connected' => $this->entityManager->getConnection()->isConnected(),
    ]);
}
}