<?php

namespace App\Controller\Admin;

use App\Entity\Bicycle;
use App\Entity\BicycleStation;
use App\Entity\BicycleRental;
use App\Entity\Location;
use App\Enum\BICYCLE_STATUS;
use App\Enum\BICYCLE_STATION_STATUS;
use App\Form\BicycleType;
use App\Form\BicycleStationType;
use App\Service\BicycleService;
use App\Service\BicycleStationService;
use App\Service\BicycleRentalService;
use App\Service\LocationService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use App\entity\User;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

//#[IsGranted('ROLE_ADMIN')]
#[Route('/admin/bicycle')]
class BicycleAdminController extends AbstractController
{
    private $entityManager;
    private $bicycleService;
    private $stationService;
    private $rentalService;
    private $locationService;
    private $logger;
    private $paginator;

    public function __construct(
        EntityManagerInterface $entityManager,
        BicycleService $bicycleService,
        BicycleStationService $stationService,
        BicycleRentalService $rentalService,
        LocationService $locationService,
        LoggerInterface $logger,
        PaginatorInterface $paginator
    ) {
        $this->entityManager = $entityManager;
        $this->bicycleService = $bicycleService;
        $this->stationService = $stationService;
        $this->rentalService = $rentalService;
        $this->locationService = $locationService;
        $this->logger = $logger;
        $this->paginator = $paginator;
    }

    #[Route('/add', name: 'admin_bicycle_add', methods: ['GET', 'POST'])]
    public function create(Request $request, EntityManagerInterface $entityManager): Response
    {
        $bicycle = new Bicycle();
        $form = $this->createForm(BicycleType::class, $bicycle);
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($bicycle);
            $entityManager->flush();
    
            $this->addFlash('success', 'Bicycle created successfully.');
    
            // Redirect back to the page that shows rentals (modal is in that page)
            return $this->redirectToRoute('admin_bicycle_rentals', ['tab' => $activeTab]);
        }
    
        return $this->redirectToRoute('admin_bicycle_rentals');
    }
    #[Route('/{id}/edit', name: 'admin_bicycle_edit', methods: ['GET', 'POST'])]
    public function editBicycle(
        int $id, 
        Request $request, 
        EntityManagerInterface $em,
        ValidatorInterface $validator
    ): Response {
        // Find the bicycle by ID
        $bicycle = $em->getRepository(Bicycle::class)->find($id); // Fetch the bicycle by the ID parameter
        if (!$bicycle) {
            $this->addFlash('error', 'Bicycle not found.');
            return $this->redirectToRoute('admin_bicycle_rentals', ['tab' => $activeTab]);
        }
    
        // Ensure the status is set
        if (!$bicycle->getStatus()) {
            $bicycle->setStatus(BICYCLE_STATUS::AVAILABLE);
        }
    
        // Create the form with the Bicycle entity
        $form = $this->createForm(BicycleType::class, $bicycle);
    
        // Handle the form submission
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                // Manually validate the entity
                $validationErrors = $validator->validate($bicycle);
                if (count($validationErrors) > 0) {
                    // Collect validation errors
                    $errorMessages = [];
                    foreach ($validationErrors as $error) {
                        $errorMessages[] = $error->getPropertyPath() . ': ' . $error->getMessage();
                    }
                    // Log the validation errors
                    $this->logger->error('Entity validation errors: ', $errorMessages);
                    $this->addFlash('error', 'Validation errors: ' . implode(', ', $errorMessages));
    
                    // Return to the list page with errors
                    return $this->redirectToRoute('admin_bicycle_rentals', ['tab' => $activeTab]);
                }
    
                // Save the updated bicycle to the database
                $em->flush();
    
                // Success message
                $this->addFlash('success', 'Bicycle updated successfully!');
    
                // Redirect to bicycle rentals page
                return $this->redirectToRoute('admin_bicycle_rentals', ['tab' => 'bicycles']);
            } catch (\Exception $e) {
                // Log the error and display a flash message
                $this->logger->error('Error saving bicycle: ' . $e->getMessage());
                $this->addFlash('error', 'Error saving bicycle: ' . $e->getMessage());
            }
        }
    
        // If form is not submitted or invalid, display the form again
        return $this->redirectToRoute('admin_bicycle_rentals', ['tab' => $activeTab]);
    }
    

    
    
    #[Route('/bicycle/{id}/data', name: 'admin_bicycle_data', methods: ['GET'])]
    public function bicycleData(int $id): JsonResponse
    {
        try {
            $bicycle = $this->bicycleService->getBicycle($id);
            
            if (!$bicycle) {
                return new JsonResponse(['error' => 'Bicycle not found'], 404);
            }
            
            // Return a complete set of data in a predictable format
            return new JsonResponse([
                'idBike' => $bicycle->getIdBike(),
                'status' => $bicycle->getStatus()->value,
                'batteryLevel' => $bicycle->getBatteryLevel(),
                'rangeKm' => $bicycle->getRangeKm(),
                'stationId' => $bicycle->getBicycleStation() ? $bicycle->getBicycleStation()->getIdStation() : null,
                'lastUpdated' => $bicycle->getLastUpdated()->format('Y-m-d H:i:s'),
                'success' => true
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Error getting bicycle data: ' . $e->getMessage());
            return new JsonResponse([
                'error' => 'Error retrieving bicycle data',
                'message' => $e->getMessage(),
                'success' => false
            ], 500);
        }
    }
    
    #[Route('/bicycle/get-details', name: 'admin_bicycle_get_details', methods: ['GET'])]
    public function getBicycleDetails(Request $request): JsonResponse
    {
        $id = $request->query->get('id');
        $bicycle = $this->bicycleService->getBicycle((int)$id);
        
        if (!$bicycle) {
            return new JsonResponse(['error' => 'Bicycle not found'], 404);
        }
        
        // Return bicycle data as JSON
        return new JsonResponse([
            'id' => $bicycle->getIdBike(),
            'status' => $bicycle->getStatus()->value,
            'batteryLevel' => $bicycle->getBatteryLevel(),
            'rangeKm' => $bicycle->getRangeKm(),
            'stationId' => $bicycle->getBicycleStation() ? $bicycle->getBicycleStation()->getIdStation() : null,
            'lastUpdated' => $bicycle->getLastUpdated()->format('Y-m-d H:i:s')
        ]);
    }
    
    #[Route('/bicycle/{id}/json', name: 'admin_bicycle_json', methods: ['GET'])]
    public function bicycleJson(int $id): JsonResponse
    {
        $bicycle = $this->bicycleService->getBicycle($id);
        
        if (!$bicycle) {
            return new JsonResponse(['error' => 'Bicycle not found'], 404);
        }
        
        return new JsonResponse([
            'idBike' => $bicycle->getIdBike(),
            'status' => [
                'value' => $bicycle->getStatus()->value,
                'name' => $bicycle->getStatus()->name
            ],
            'batteryLevel' => $bicycle->getBatteryLevel(),
            'rangeKm' => $bicycle->getRangeKm(),
            'bicycleStation' => $bicycle->getBicycleStation() ? [
                'idStation' => $bicycle->getBicycleStation()->getIdStation(),
                'name' => $bicycle->getBicycleStation()->getName()
            ] : null,
            'lastUpdated' => $bicycle->getLastUpdated()->format('Y-m-d H:i:s')
        ]);
    }
    
    #[Route('/bicycle/delete', name: 'admin_bicycle_delete', methods: ['POST'])]
    public function deleteBicycle(Request $request): Response
    {
        $bicycleId = $request->request->get('bicycleId');
        $bicycle = $this->bicycleService->getBicycle((int)$bicycleId);
        
        // Get the referring URL or tab to redirect back correctly
        $referer = $request->headers->get('referer');
        $activeTab = 'bicycles'; // Default tab
        
        // Check if the referer contains a tab parameter
        if ($referer && preg_match('/tab=([^&]+)/', $referer, $matches)) {
            $activeTab = $matches[1];
        }
        
        if (!$bicycle) {
            $this->addFlash('error', 'Bicycle not found');
        } else {
            try {
                $this->bicycleService->deleteBicycle($bicycle);
                $this->addFlash('success', 'Bicycle deleted successfully');
            } catch (\Exception $e) {
                $this->logger->error('Error deleting bicycle: ' . $e->getMessage());
                $this->addFlash('error', 'Error deleting bicycle: ' . $e->getMessage());
            }
        }
        
        // Redirect back to the appropriate tab
        return $this->redirectToRoute('admin_bicycle_rentals', ['tab' => $activeTab]);
    }
    
    #[Route('/bicycle/change-status', name: 'admin_bicycle_change_status', methods: ['POST'])]
    public function changeBicycleStatus(Request $request): Response
    {
        $bicycleId = $request->request->get('bicycleId');
        $statusValue = $request->request->get('status');
        
        try {
            $bicycle = $this->bicycleService->getBicycle((int)$bicycleId);
            
            if (!$bicycle) {
                $this->addFlash('error', 'Bicycle not found');
            } else {
                $status = \App\Enum\BICYCLE_STATUS::from($statusValue);
                $this->bicycleService->changeBicycleStatus($bicycle, $status);
                $this->addFlash('success', sprintf('Bicycle status changed to %s', $status->value));
            }
        } catch (\Exception $e) {
            $this->logger->error('Error changing bicycle status: ' . $e->getMessage());
            $this->addFlash('error', 'Error changing bicycle status: ' . $e->getMessage());
        }
        
        return $this->redirectToRoute('admin_bicycle_dashboard', ['tab' => 'bicycles']);
    }
    
    #[Route('/bicycle/schedule-maintenance', name: 'admin_bicycle_schedule_maintenance', methods: ['POST'])]
    public function scheduleMaintenance(Request $request): Response
    {
        $bicycleIds = $request->request->get('bicycleIds', []);
        $maintenanceType = $request->request->get('maintenanceType');
        $notes = $request->request->get('notes');
        
        $success = 0;
        $failures = 0;
        
        foreach ($bicycleIds as $id) {
            try {
                $bicycle = $this->bicycleService->getBicycle((int)$id);
                if ($bicycle) {
                    $this->bicycleService->changeBicycleStatus($bicycle, \App\Enum\BICYCLE_STATUS::MAINTENANCE);
                    
                    // Here you would also log the maintenance request with notes and type
                    // For example:
                    // $this->maintenanceService->logMaintenanceRequest($bicycle, $maintenanceType, $notes);
                    
                    $success++;
                } else {
                    $failures++;
                }
            } catch (\Exception $e) {
                $this->logger->error('Error scheduling maintenance for bicycle #' . $id . ': ' . $e->getMessage());
                $failures++;
            }
        }
        
        if ($success > 0) {
            $this->addFlash('success', sprintf('%d bicycle(s) scheduled for maintenance successfully', $success));
        }
        
        if ($failures > 0) {
            $this->addFlash('error', sprintf('Failed to schedule maintenance for %d bicycle(s)', $failures));
        }
        
        return $this->redirectToRoute('admin_bicycle_dashboard', ['tab' => 'bicycles']);
    }

    // Station Management Routes
    
    #[Route('/stations', name: 'admin_bicycle_stations')]
    public function bicycleStations(Request $request): Response
    {
        // Create a new station form
        $station = new BicycleStation();
        $station->setStatus(BICYCLE_STATION_STATUS::ACTIVE);
        $station->setChargingBikes(0);
        $stationForm = $this->createForm(BicycleStationType::class, $station);
        
        // Get all stations with their details
        $stations = $this->stationService->getAllStations();

        // Get station counts by status
        $stationCounts = $this->stationService->getStationCountsByStatus();

        // Get total capacity and charging docks
        $totalCapacity = $this->stationService->getTotalBicycleCapacity();
        $totalChargingDocks = $this->stationService->getTotalChargingDocks();

        // Get stations with rental activity
        $stationActivity = $this->stationService->getStationsWithRentalActivity(5);

        return $this->render('back-office/bicycle/station.html.twig', [
            'bicycles' => $this->bicycleService->getAllBicycles(),
            'stations' => $stations,
            'bicycle_rentals' => $this->entityManager->getRepository(BicycleRental::class)->findAll(),
            'stationCounts' => $stationCounts,
            'totalCapacity' => $totalCapacity,
            'totalChargingDocks' => $totalChargingDocks,
            'stationActivity' => $stationActivity,
            'tab' => 'stations',
            'stationForm' => $stationForm->createView()
        ]);
    }

    #[Route('/station/new', name: 'admin_bicycle_station_new', methods: ['POST'])]
    public function newStation(Request $request): JsonResponse
    {
        try {
            // Create a new station
            $station = new BicycleStation();
            $form = $this->createForm(BicycleStationType::class, $station);
            
            // Submit the form without validation to get the data
            $form->handleRequest($request);
            
            // Get location data from custom fields that are not part of the form
            $latitude = $request->request->get('station_latitude');
            $longitude = $request->request->get('station_longitude');
            $address = $request->request->get('station_address');
            $locationId = $request->request->get('station_location_id');
            $availableBikes = (int)$request->request->get('availableBikes', 0);
            
            // Now validate the form
            if ($form->isSubmitted() && $form->isValid()) {
                // Set calculated values
                $totalDocks = $station->getTotalDocks();
                $station->setAvailableBikes($availableBikes);
                $station->setAvailableDocks($totalDocks - $availableBikes);
                $station->setChargingBikes(0); // Initialize charging bikes to 0
                
                // Handle location
                if ($locationId) {
                    // Use existing location
                    $location = $this->locationService->findLocationById((int)$locationId);
                    if ($location) {
                        $station->setLocation($location);
                    }
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
                    'message' => 'Station created successfully',
                    'stationId' => $station->getIdStation()
                ]);
            } else {
                // Form validation failed
                $errors = [];
                foreach ($form->getErrors(true) as $error) {
                    $errors[] = $error->getMessage();
                }
                
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Validation failed: ' . implode(', ', $errors)
                ], 400);
            }
        } catch (\Exception $e) {
            // Log the exception with detailed information
            $this->logger->error('Error creating station: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);
            
            return new JsonResponse([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    #[Route('/bicycle/station/{id}/edit', name: 'admin_bicycle_station_edit', methods: ['POST'])]
    public function editStation(Request $request, int $id): JsonResponse
    {
        try {
            // Find the station
            $station = $this->entityManager->getRepository(BicycleStation::class)->find($id);
            
            if (!$station) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Station not found'
                ], 404);
            }
            
            // Create form for the station
            $form = $this->createForm(BicycleStationType::class, $station);
            $form->handleRequest($request);
            
            // Get additional data from request that's not part of the form
            $availableBikes = (int)$request->request->get('availableBikes', $station->getAvailableBikes());
            $latitude = $request->request->get('station_latitude');
            $longitude = $request->request->get('station_longitude');
            $address = $request->request->get('station_address');
            $locationId = $request->request->get('station_location_id');
            
            if ($form->isSubmitted() && $form->isValid()) {
                // Update calculated fields
                $totalDocks = $station->getTotalDocks();
                $station->setAvailableBikes($availableBikes);
                $station->setAvailableDocks($totalDocks - $availableBikes);
                
                // Handle location update
                if ($locationId) {
                    // Use existing location
                    $location = $this->locationService->findLocationById((int)$locationId);
                    if ($location) {
                        $station->setLocation($location);
                    }
                } else if ($latitude && $longitude) {
                    // Create or update location
                    $location = $station->getLocation();
                    
                    if (!$location) {
                        $location = new Location();
                        $this->entityManager->persist($location);
                    }
                    
                    $location->setLatitude((float)$latitude);
                    $location->setLongitude((float)$longitude);
                    $location->setAddress($address ?: 'Unknown location');
                    $station->setLocation($location);
                }
                
                // Save the updated station
                $this->entityManager->flush();
                
                return new JsonResponse([
                    'success' => true,
                    'message' => 'Station updated successfully'
                ]);
            } else {
                // Form validation failed
                $errors = [];
                foreach ($form->getErrors(true) as $error) {
                    $errors[] = $error->getMessage();
                }
                
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Validation failed: ' . implode(', ', $errors)
                ], 400);
            }
        } catch (\Exception $e) {
            $this->logger->error('Error updating station: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);
            
            return new JsonResponse([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    #[Route('/station/{id}', name: 'admin_bicycle_station_detail')]
    public function stationDetail(int $id): Response
    {
        $station = $this->stationService->getStation($id);

        if (!$station) {
            $this->addFlash('error', 'Station not found.');
            return $this->redirectToRoute('admin_bicycle_dashboard', ['tab' => 'stations']);
        }

        // Get bicycles at this station
        $bicycles = $this->bicycleService->getBicyclesByStation($station);

        // Get rental history for this station
        $rentals = $this->rentalService->getRentalsByStation($station);

        // Get bicycles by status for this station
        $bicyclesByStatus = [
            'available' => 0,
            'in_use' => 0,
            'maintenance' => 0,
            'charging' => 0,
            'reserved' => 0
        ];

        foreach ($bicycles as $bicycle) {
            $status = $bicycle->getStatus()->value;
            if (isset($bicyclesByStatus[$status])) {
                $bicyclesByStatus[$status]++;
            }
        }

        // Get station statistics
        $statistics = [
            'totalRentals' => count($rentals),
            'availableBikes' => $station->getAvailableBikes(),
            'availableDocks' => $station->getAvailableDocks(),
            'totalDocks' => $station->getTotalDocks(),
            'occupancyRate' => $station->getTotalDocks() > 0
                ? ($station->getAvailableBikes() / $station->getTotalDocks()) * 100
                : 0,
        ];

        return $this->render('back-office/bicycle/station-detail.html.twig', [
            'station' => $station,
            'bicycles' => $bicycles,
            'rentals' => $rentals,
            'bicyclesByStatus' => $bicyclesByStatus,
            'statistics' => $statistics,
        ]);
    }

    #[Route('/station/{id}/maintenance', name: 'admin_bicycle_station_maintenance')]
    public function setStationMaintenance(int $id): Response
    {
        $station = $this->stationService->getStation($id);

        if (!$station) {
            $this->addFlash('error', 'Station not found.');
            return $this->redirectToRoute('admin_bicycle_stations');
        }

        try {
            $station->setStatus(BICYCLE_STATION_STATUS::MAINTENANCE);
            $this->stationService->updateStation($station);
            $this->addFlash('success', sprintf('Station "%s" has been set to maintenance mode.', $station->getName()));
        } catch (\Exception $e) {
            $this->logger->error('Failed to update station status: ' . $e->getMessage());
            $this->addFlash('error', 'Failed to update station status: ' . $e->getMessage());
        }

        return $this->redirectToRoute('admin_bicycle_stations', ['tab' => 'stations']);
    }

    #[Route('/station/{id}/activate', name: 'admin_bicycle_station_activate')]
    public function activateStation(int $id): Response
    {
        $station = $this->stationService->getStation($id);

        if (!$station) {
            $this->addFlash('error', 'Station not found.');
            return $this->redirectToRoute('admin_bicycle_stations');
        }

        try {
            $station->setStatus(BICYCLE_STATION_STATUS::ACTIVE);
            $this->stationService->updateStation($station);
            $this->addFlash('success', sprintf('Station "%s" has been activated.', $station->getName()));
        } catch (\Exception $e) {
            $this->logger->error('Failed to update station status: ' . $e->getMessage());
            $this->addFlash('error', 'Failed to update station status: ' . $e->getMessage());
        }

        return $this->redirectToRoute('admin_bicycle_stations', ['tab' => 'stations']);
    }

    #[Route('/station/{id}/delete', name: 'admin_bicycle_station_delete', methods: ['POST', 'GET'])]
    public function deleteStation(int $id, Request $request): Response
    {
        try {
            // CSRF check has been disabled
            
            // Find the station
            $station = $this->entityManager->getRepository(BicycleStation::class)->find($id);
            
            if (!$station) {
                $this->addFlash('error', 'Station not found.');
                return $this->redirectToRoute('admin_bicycle_stations');
            }
            
            // Check if there are bicycles at this station
            $bicycles = $this->bicycleService->getBicyclesByStation($station);
            if (count($bicycles) > 0) {
                $this->addFlash('error', 'Cannot delete station: please reassign all bicycles from this station first.');
                return $this->redirectToRoute('admin_bicycle_stations');
            }
            
            // Remove the station
            $stationName = $station->getName(); // Store name before deletion
            $this->entityManager->remove($station);
            $this->entityManager->flush();
            
            // Success message
            $this->addFlash('success', sprintf('Station "%s" has been successfully deleted.', $stationName));
        } catch (\Exception $e) {
            $this->logger->error('Error deleting station: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);
            
            $this->addFlash('error', 'An error occurred while trying to delete the station: ' . $e->getMessage());
        }
        
        return $this->redirectToRoute('admin_bicycle_stations');
    }

    // API Routes

    #[Route('/api/bicycle-stations', name: 'api_bicycle_stations', methods: ['GET'])]
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
    
    // Added an alias route for the bicycle maps to use
    #[Route('/api/admin-bicycle-stations', name: 'admin_bicycle_api_stations', methods: ['GET'])]
    public function getAdminBicycleStations(): JsonResponse
    {
        return $this->getApiStations(); // Reuse the existing implementation
    }

    #[Route('/api/stations', name: 'api_create_station', methods: ['POST'])]
    public function createStation(Request $request): JsonResponse
    {
        try {
            // Decode JSON data
            $content = $request->getContent();
            if (empty($content)) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'No data received'
                ], 400);
            }
            
            $data = json_decode($content, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Invalid JSON: ' . json_last_error_msg()
                ], 400);
            }
            
            // Log received data for debugging
            $this->logger->info('Received station creation request', $data ?? []);
            
            // Validate required fields
            $requiredFields = ['name', 'totalDocks', 'availableBikes', 'status'];
            $missingFields = [];
            
            foreach ($requiredFields as $field) {
                if (!isset($data[$field]) || (is_string($data[$field]) && trim($data[$field]) === '')) {
                    $missingFields[] = $field;
                }
            }
            
            if (!empty($missingFields)) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Missing required fields: ' . implode(', ', $missingFields)
                ], 400);
            }
            
            // Create a new station
            $station = new BicycleStation();
            $station->setName($data['name']);
            $station->setTotalDocks((int)$data['totalDocks']);
            $station->setAvailableBikes((int)$data['availableBikes']);
            $station->setAvailableDocks((int)$data['totalDocks'] - (int)$data['availableBikes']);
            $station->setChargingBikes(0); // Initialize charging bikes
            
            // Set status
            try {
                $station->setStatus(BICYCLE_STATION_STATUS::from($data['status']));
            } catch (\Exception $e) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Invalid status value: ' . $data['status']
                ], 400);
            }
                
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
                'exception' => $e,
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
                
            return new JsonResponse([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
                'details' => 'Error occurred in ' . $e->getFile() . ' on line ' . $e->getLine()
            ], 500);
        }
    }

    #[Route('/api/locations', name: 'api_locations', methods: ['GET'])]
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
    #[Route('/station/{id}/edit', name: 'admin_bicycle_station_edit_form', methods: ['GET'])]
    public function getStationEditForm(int $id): Response
    {
        try {
            $this->logger->info('Getting station edit form for ID: ' . $id);
            
            $station = $this->stationService->getStation($id);
            
            if (!$station) {
                $this->logger->warning('Station not found for ID: ' . $id);
                return new JsonResponse(['error' => 'Station not found'], 404);
            }
            
            // Create form for the station
            $form = $this->createForm(BicycleStationType::class, $station);
            
            $this->logger->info('Found station: ' . $station->getName());
            $this->logger->info('Rendering template: back-office/bicycle/station-edit.html.twig');
            
            return $this->render('back-office/bicycle/station-edit.html.twig', [
                'station' => $station,
                'stationForm' => $form->createView()
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Error rendering station edit form: ' . $e->getMessage(), [
                'exception' => $e,
                'stationId' => $id,
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            
            return new JsonResponse([
                'error' => 'Server error: ' . $e->getMessage()
            ], 500);
        }
    }
    #[Route('/bicycle/bulk-assign-station', name: 'admin_bicycle_bulk_assign_station', methods: ['POST'])]
    public function bulkAssignBicyclesToStation(Request $request, ValidatorInterface $validator): JsonResponse
    {
        $response = null;
        $data = json_decode($request->getContent(), true);
        
        // Handle invalid data format
        if (!$data || !isset($data['assignments']) || !is_array($data['assignments'])) {
            $response = new JsonResponse(['success' => false, 'message' => 'Invalid data format'], 400);
        } else {
            $assignments = $data['assignments'];
            $updatedCount = 0;
            $errors = [];
            
            $this->entityManager->beginTransaction();
            
            try {
                foreach ($assignments as $assignment) {
                    $bicycleId = $assignment['bicycleId'] ?? null;
                    $stationId = $assignment['stationId'] ?? null;
                    
                    if (!$bicycleId || !$stationId) {
                        $errors[] = 'Missing bicycle ID or station ID';
                        continue;
                    }
                    
                    $bicycle = $this->bicycleService->getBicycle((int)$bicycleId);
                    $station = $this->stationService->getStation((int)$stationId);
                    
                    if (!$bicycle) {
                        $errors[] = "Bicycle #$bicycleId not found";
                        continue;
                    }
                    
                    if (!$station) {
                        $errors[] = "Station #$stationId not found";
                        continue;
                    }
                    
                    // Check if station has available docks
                    if ($station->getAvailableDocks() <= 0) {
                        $errors[] = "Station {$station->getName()} has no available docks";
                        continue;
                    }
                    
                    // Update bicycle station
                    $bicycle->setBicycleStation($station);
                    
                    // Validate
                    $validationErrors = $validator->validate($bicycle);
                    if (count($validationErrors) > 0) {
                        $errorMessages = [];
                        foreach ($validationErrors as $error) {
                            $errorMessages[] = $error->getMessage();
                        }
                        $errors[] = "Validation failed for bicycle #$bicycleId: " . implode(', ', $errorMessages);
                        continue;
                    }
                    
                    // Update station available docks/bikes
                    $station->setAvailableDocks($station->getAvailableDocks() - 1);
                    $station->setAvailableBikes($station->getAvailableBikes() + 1);
                    
                    // Success
                    $updatedCount++;
                }
                
                if ($updatedCount > 0) {
                    $this->entityManager->flush();
                    $this->entityManager->commit();
                    
                    $response = new JsonResponse([
                        'success' => true, 
                        'updatedCount' => $updatedCount,
                        'errors' => $errors
                    ]);
                } else {
                    $this->entityManager->rollback();
                    
                    $response = new JsonResponse([
                        'success' => false,
                        'message' => 'No bicycles were assigned. ' . implode('; ', $errors)
                    ], 400);
                }
            } catch (\Exception $e) {
                $this->entityManager->rollback();
                $this->logger->error('Error assigning bicycles to station: ' . $e->getMessage());
                
                $response = new JsonResponse([
                    'success' => false,
                    'message' => 'Error: ' . $e->getMessage()
                ], 500);
            }
        }
        
        return $response;
    }

    /**
     * Helper function to get bicycle status choices
     */
    private function getStatusChoices(): array
    {
        $choices = [];
        foreach (BICYCLE_STATUS::cases() as $status) {
            $label = ucfirst(strtolower(str_replace('_', ' ', $status->name)));
            $choices[$label] = $status->value;
        }
        return $choices;
    }
}