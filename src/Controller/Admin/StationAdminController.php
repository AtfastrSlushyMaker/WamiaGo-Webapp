<?php

namespace App\Controller\Admin;

use App\Entity\BicycleRental;
use App\Entity\BicycleStation;
use App\Entity\Location;
use App\Enum\BICYCLE_STATION_STATUS;
use App\Service\BicycleService;
use App\Service\BicycleStationService;
use App\Service\BicycleRentalService;
use App\Service\LocationService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use ValueError; // Import ValueError for catching enum errors

//#[IsGranted('ROLE_ADMIN')]
#[Route('/admin/bicycle/station')]
class StationAdminController extends AbstractController
{
    private $entityManager;
    private $bicycleService;
    private $stationService;
    private $rentalService;
    private $locationService;
    private $logger;

    public function __construct(
        EntityManagerInterface $entityManager,
        BicycleService $bicycleService,
        BicycleStationService $stationService,
        BicycleRentalService $rentalService,
        LocationService $locationService,
        LoggerInterface $logger
    ) {
        $this->entityManager = $entityManager;
        $this->bicycleService = $bicycleService;
        $this->stationService = $stationService;
        $this->rentalService = $rentalService;
        $this->locationService = $locationService;
        $this->logger = $logger;
    }

    #[Route('s', name: 'admin_bicycle_stations')]
    public function index(): Response
    {
        // Get all stations with their details
        $stations = $this->stationService->getAllStations();

        // Get station counts by status
        $stationCounts = $this->stationService->getStationCountsByStatus();

        // Get total capacity and charging docks
        $totalCapacity = $this->stationService->getTotalBicycleCapacity();
        $totalChargingDocks = $this->stationService->getTotalChargingDocks();

        // Get stations with rental activity
        $stationActivity = $this->stationService->getStationsWithRentalActivity(5);

        return $this->render('back-office/bicycle-rentals.html.twig', [
            'bicycles' => $this->bicycleService->getAllBicycles(),
            'stations' => $stations,
            'bicycle_rentals' => $this->entityManager->getRepository(BicycleRental::class)->findAll(),
            'stationCounts' => $stationCounts,
            'totalCapacity' => $totalCapacity,
            'totalChargingDocks' => $totalChargingDocks,
            'stationActivity' => $stationActivity,
            'tab' => 'stations'
        ]);
    }

    #[Route('/station/new', name: 'admin_bicycle_station_new', methods: ['POST'])]
    public function newStation(Request $request, ValidatorInterface $validator): JsonResponse
    {
        try {
            // Create a new station
            $station = new BicycleStation();
            
            // Get data from request
            $name = $request->request->get('name');
            $totalDocks = (int)$request->request->get('totalDocks');
            $availableBikes = (int)$request->request->get('availableBikes');
            $statusValue = $request->request->get('status');
            $latitude = $request->request->get('latitude');
            $longitude = $request->request->get('longitude');
            $address = $request->request->get('address');
            $locationId = $request->request->get('locationId');
            
            // Set basic station properties
            $station->setName($name);
            $station->setTotalDocks($totalDocks);
            $station->setAvailableBikes($availableBikes);
            $station->setAvailableDocks($totalDocks - $availableBikes);
            $station->setChargingBikes(0); // Initialize charging bikes to 0
            
            // Set status using the enum
            if ($statusValue) {
                $station->setStatus(BICYCLE_STATION_STATUS::from($statusValue));
            } else {
                $station->setStatus(BICYCLE_STATION_STATUS::ACTIVE);
            }
            
            // Handle location
            $location = null;
            
            if ($locationId) {
                // Use existing location
                $location = $this->locationService->getLocation((int)$locationId);
                if (!$location) {
                    return new JsonResponse(['success' => false, 'message' => 'Location not found'], 404);
                }
            } else if ($latitude && $longitude) {
                // Create new location
                $location = new Location();
                $location->setLatitude($latitude);
                $location->setLongitude($longitude);
                $location->setAddress($address ?: 'Unknown location');
                
                // Validate location
                $locationErrors = $validator->validate($location);
                if (count($locationErrors) > 0) {
                    $errorMessages = [];
                    foreach ($locationErrors as $error) {
                        $errorMessages[] = $error->getMessage();
                    }
                    return new JsonResponse([
                        'success' => false,
                        'message' => 'Location validation failed: ' . implode(', ', $errorMessages)
                    ], 400);
                }
            } else {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Location data is required'
                ], 400);
            }
            
            // Set the location for the station
            if ($location) {
                $station->setLocation($location);
            }
            
            // Validate the station entity
            $errors = $validator->validate($station);
            if (count($errors) > 0) {
                $errorMessages = [];
                foreach ($errors as $error) {
                    $errorMessages[] = $error->getMessage();
                }
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Station validation failed: ' . implode(', ', $errorMessages)
                ], 400);
            }
            
            // Save everything to the database
            $this->entityManager->persist($location);
            $this->entityManager->persist($station);
            $this->entityManager->flush();
            
            return new JsonResponse([
                'success' => true,
                'message' => 'Station created successfully',
                'station' => [
                    'id' => $station->getIdStation(),
                    'name' => $station->getName(),
                    'status' => $station->getStatus()->value,
                    'totalDocks' => $station->getTotalDocks(),
                    'availableBikes' => $station->getAvailableBikes(),
                    'availableDocks' => $station->getAvailableDocks()
                ]
            ]);
        } catch (\Exception $e) {
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

    #[Route('/station/{id}/edit', name: 'admin_bicycle_station_edit', methods: ['POST'])]
    public function editStation(int $id, Request $request, ValidatorInterface $validator): JsonResponse
    {
        try {
            $this->logger->info('Updating station with ID: ' . $id);

            // Get the station
            $station = $this->stationService->getStation($id);
            if (!$station) {
                return new JsonResponse(['success' => false, 'message' => 'Station not found'], 404);
            }

            // Get form data
            $name = $request->request->get('name');
            $statusValue = $request->request->get('status');
            $totalDocks = (int) $request->request->get('totalDocks');
            $availableBikes = (int) $request->request->get('availableBikes');

            // Check if using existing location or creating new one
            $locationId = $request->request->get('locationId');

            // Get location data
            $latitude = $request->request->get('latitude');
            $longitude = $request->request->get('longitude');
            $address = $request->request->get('address');

            // Update station properties
            $station->setName($name);

            // Validate and set status using the enum
            if ($statusValue) {
                try {
                    $statusEnum = BICYCLE_STATION_STATUS::from($statusValue);
                    $station->setStatus($statusEnum);
                } catch (ValueError $e) {
                    $this->logger->warning('Invalid status value provided for station update: ' . $statusValue, ['stationId' => $id]);
                    return new JsonResponse([
                        'success' => false,
                        'message' => 'Invalid status value provided: ' . $statusValue
                    ], 400);
                }
            } else {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Status is required.'
                ], 400);
            }

            $station->setTotalDocks($totalDocks);
            $station->setAvailableBikes($availableBikes);
            $station->setAvailableDocks($totalDocks - $availableBikes);

            // Handle location
            $location = null;

            if ($locationId) {
                $location = $this->locationService->getLocation((int) $locationId);
                if (!$location) {
                    return new JsonResponse(['success' => false, 'message' => 'Location not found'], 404);
                }
                if ($latitude && $longitude) {
                    $location->setLatitude($latitude);
                    $location->setLongitude($longitude);
                    $location->setAddress($address ?: $location->getAddress());
                }
            } elseif ($latitude && $longitude) {
                $location = $station->getLocation();
                if (!$location) {
                    $location = new Location();
                }
                $location->setLatitude($latitude);
                $location->setLongitude($longitude);
                $location->setAddress($address ?: 'Unknown location');
            }

            if ($location && ($location->getIdLocation() === null || $locationId || ($latitude && $longitude))) {
                $locationErrors = $validator->validate($location);
                if (count($locationErrors) > 0) {
                    $errorMessages = [];
                    foreach ($locationErrors as $error) {
                        $errorMessages[] = $error->getMessage();
                    }
                    return new JsonResponse([
                        'success' => false,
                        'message' => 'Location validation failed: ' . implode(', ', $errorMessages)
                    ], 400);
                }
                $this->entityManager->persist($location);
            }

            if ($location) {
                $station->setLocation($location);
            }

            $errors = $validator->validate($station);
            if (count($errors) > 0) {
                $errorMessages = [];
                foreach ($errors as $error) {
                    $errorMessages[] = $error->getMessage();
                }
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Station validation failed: ' . implode(', ', $errorMessages)
                ], 400);
            }

            $this->entityManager->persist($station);
            $this->entityManager->flush();

            $this->logger->info('Station updated successfully', ['stationId' => $id]);

            // Return updated station data in the response
            return new JsonResponse([
                'success' => true,
                'message' => 'Station updated successfully',
                'station' => [
                    'id' => $station->getIdStation(),
                    'name' => $station->getName(),
                    'status' => $station->getStatus()->value,
                    'totalDocks' => $station->getTotalDocks(),
                    'availableBikes' => $station->getAvailableBikes(),
                    'availableDocks' => $station->getAvailableDocks(),
                    'latitude' => $station->getLocation()?->getLatitude(),
                    'longitude' => $station->getLocation()?->getLongitude(),
                    'address' => $station->getLocation()?->getAddress(),
                ]
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Error updating station: ' . $e->getMessage(), [
                'exception' => $e,
                'stationId' => $id,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return new JsonResponse([
                'success' => false,
                'message' => 'An internal error occurred while updating the station. Please check logs.'
            ], 500);
        }
    }

    #[Route('/{id}', name: 'admin_bicycle_station_detail')]
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

    #[Route('/{id}/maintenance', name: 'admin_bicycle_station_maintenance')]
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

    #[Route('/{id}/activate', name: 'admin_bicycle_station_activate')]
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

    #[Route('/station/{id}/log-maintenance', name: 'admin_bicycle_station_log_maintenance', methods: ['POST'])]
    public function logMaintenance(int $id, Request $request): JsonResponse
    {
        $station = $this->stationService->getStation($id);

        if (!$station) {
            return new JsonResponse(['success' => false, 'message' => 'Station not found'], 404);
        }

        $activityType = $request->request->get('activityType');
        $description = $request->request->get('description');

        // Log maintenance activity (this could involve saving to a database or updating a log file)
        $this->logger->info(sprintf('Maintenance logged for station %s: %s - %s', $station->getName(), $activityType, $description));

        return new JsonResponse(['success' => true, 'message' => 'Maintenance activity logged successfully']);
    }
}