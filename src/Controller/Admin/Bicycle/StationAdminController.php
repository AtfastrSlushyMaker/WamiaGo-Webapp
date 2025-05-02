<?php

namespace App\Controller\Admin\Bicycle;

use App\Entity\BicycleRental;
use App\Entity\BicycleStation;
use App\Entity\Location;
use App\Enum\BICYCLE_STATION_STATUS;
use App\Service\BicycleService;
use App\Service\BicycleStationService;
use App\Service\BicycleRentalService;
use App\Service\LocationService;
use App\Service\ExportService;
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
use Knp\Component\Pager\PaginatorInterface;
use App\Form\BicycleStationType;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

#[Route('/admin/bicycle/station')]
class StationAdminController extends AbstractController
{
    private $entityManager;
    private $bicycleService;
    private $stationService;
    private $rentalService;
    private $locationService;
    private $logger;
    private $paginator;
    private $exportService;

    public function __construct(
        EntityManagerInterface $entityManager,
        BicycleService $bicycleService,
        BicycleStationService $stationService,
        BicycleRentalService $rentalService,
        LocationService $locationService,
        LoggerInterface $logger,
        PaginatorInterface $paginator,
        ExportService $exportService
    ) {
        $this->entityManager = $entityManager;
        $this->bicycleService = $bicycleService;
        $this->stationService = $stationService;
        $this->rentalService = $rentalService;
        $this->locationService = $locationService;
        $this->logger = $logger;
        $this->paginator = $paginator;
        $this->exportService = $exportService;
    }

    #[Route('/station/new', name: 'admin_bicycle_station_new', methods: ['POST'])]
    public function newStation(Request $request, ValidatorInterface $validator): JsonResponse
    {
        try {
            // Create a new station
            $station = new BicycleStation();
            
            // Log raw request data for debugging
            $this->logger->info('Create station request received', [
                'request_data' => $request->request->all()
            ]);
            
            // Get direct values from request (no nested arrays)
            $name = $request->request->get('name');
            $totalDocks = (int)$request->request->get('totalDocks', 0);
            $availableBikes = (int)$request->request->get('availableBikes', 0);
            $statusValue = $request->request->get('status', 'active');
            
            // Get location data
            $latitude = $request->request->get('station_latitude');
            $longitude = $request->request->get('station_longitude');
            $address = $request->request->get('station_address');
            $locationId = $request->request->get('station_location_id');
            
            // Validate required fields
            if (empty($name)) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Station name is required'
                ], 400);
            }
            
            // Set basic station properties
            $station->setName($name);
            $station->setTotalDocks($totalDocks);
            $station->setAvailableBikes($availableBikes);
            $station->setAvailableDocks($totalDocks - $availableBikes);
            $station->setChargingBikes(0);
            
            // Set status using the enum
            try {
                $station->setStatus(BICYCLE_STATION_STATUS::from($statusValue));
            } catch (\ValueError $e) {
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
            
            // Return a proper JSON response - the front-end will handle the redirect
            return new JsonResponse([
                'success' => true,
                'message' => 'Station created successfully',
                'stationId' => $station->getIdStation(),
                'redirect' => $this->generateUrl('admin_bicycle_rentals', ['tab' => 'stations'])
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

    #[Route("/{id}/delete", name: "admin_bicycle_station_delete", methods: ["POST"])]
    public function deleteStation(int $id): Response
    {
        try {
            $this->logger->info('Deleting station with ID: ' . $id);

            // Get the station
            $station = $this->stationService->getStation($id);
            if (!$station) {
                return new JsonResponse(['success' => false, 'message' => 'Station not found'], 404);
            }

            // Remove the station
            $this->entityManager->remove($station);
            $this->entityManager->flush();

            $this->logger->info('Station deleted successfully', ['stationId' => $id]);

            return $this->redirectToRoute("admin_bicycle_rentals", ['tab' => 'stations']);
        } catch (\Exception $e) {
            $this->logger->error('Error deleting station: ' . $e->getMessage(), [
                'exception' => $e,
                'stationId' => $id,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return new JsonResponse([
                'success' => false,
                'message' => 'An internal error occurred while deleting the station. Please check logs.'
            ], 500);
        }
    }

    #[Route('/{id}/edit', name: 'admin_bicycle_station_edit', methods: ['POST'])]
    public function editStation(int $id, Request $request, ValidatorInterface $validator): Response
    {
        try {
            $this->logger->info('Updating station with ID: ' . $id);

            // Get the station
            $station = $this->stationService->getStation($id);
            if (!$station) {
                return new JsonResponse(['success' => false, 'message' => 'Station not found'], 404);
            }
            
            $this->logger->info('Edit station request received', [
                'request_data' => $request->request->all()
            ]);

            // Get form data - handle both direct fields and Symfony form fields
            $name = $request->request->get('bicycle_station')['name'] 
                  ?? $request->request->get('name')
                  ?? null;
                  
            $statusValue = $request->request->get('bicycle_station')['status']
                         ?? $request->request->get('status')
                         ?? null;
                         
            $totalDocks = (int)($request->request->get('bicycle_station')['totalDocks'] 
                       ?? $request->request->get('totalDocks')
                       ?? $station->getTotalDocks());
                       
            $availableBikes = (int)($request->request->get('availableBikes') 
                           ?? $station->getAvailableBikes());

            // Validate required fields
            if (empty($name)) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Station name is required'
                ], 400);
            }

            // Get location data
            $latitude = $request->request->get('edit_latitude') 
                      ?? $request->request->get('latitude');
                      
            $longitude = $request->request->get('edit_longitude')
                       ?? $request->request->get('longitude');
                       
            $address = $request->request->get('edit_address')
                     ?? $request->request->get('address');
                     
            $locationId = $request->request->get('locationId');

            $this->logger->info('Parsed station edit data', [
                'name' => $name,
                'status' => $statusValue,
                'totalDocks' => $totalDocks,
                'availableBikes' => $availableBikes,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'address' => $address
            ]);

            // Update station properties
            $station->setName($name);

            // Validate and set status using the enum
            if ($statusValue) {
                try {
                    $statusEnum = BICYCLE_STATION_STATUS::from($statusValue);
                    $station->setStatus($statusEnum);
                } catch (ValueError $e) {
                    $this->logger->error('Invalid status value: ' . $statusValue);
                    return new JsonResponse([
                        'success' => false,
                        'message' => 'Invalid status value: ' . $statusValue
                    ], 400);
                }
            } else {
                $station->setStatus(BICYCLE_STATION_STATUS::ACTIVE);
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
                ],
                'redirect' => $this->generateUrl('admin_bicycle_rentals', ['tab' => 'stations'])
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

    #[Route('/{id}/edit-form', name: 'admin_bicycle_station_edit_form', methods: ['GET'])]
    public function editStationForm(int $id): Response
    {
        $station = $this->stationService->getStation($id);
        if (!$station) {
            return new Response('Station not found', 404);
        }
        // Create the form for AJAX editing
        $form = $this->createForm(BicycleStationType::class, $station, [
            'action' => $this->generateUrl('admin_bicycle_station_edit', ['id' => $id]),
            'method' => 'POST',
        ]);
        return $this->render('back-office/bicycle/Station/station-edit.html.twig', [
            'stationForm' => $form->createView(),
            'station' => $station,
        ]);
    }

    /**
     * Export stations data in various formats (CSV, Excel, PDF)
     */
    #[Route('/export', name: 'admin_bicycle_station_export', methods: ['GET'], priority: 10)]
    public function export(Request $request): Response
    {
        try {
          
            $this->logger->info('Starting station export process', [
                'format' => $request->query->get('format', 'csv'),
                'status' => $request->query->get('status')
            ]);
            
            
            $status = $request->query->get('status');
            $format = $request->query->get('format', 'csv');
            
            
            $queryBuilder = $this->entityManager->getRepository(BicycleStation::class)
                ->createQueryBuilder('s')
                ->leftJoin('s.location', 'l')
                ->orderBy('s.id_station', 'ASC');
            
         
            if ($status) {
                $queryBuilder->andWhere('s.status = :status')
                    ->setParameter('status', BICYCLE_STATION_STATUS::from($status));
            }
            
            $this->logger->info('Executing station query');
            $stations = $queryBuilder->getQuery()->getResult();
            $this->logger->info('Retrieved ' . count($stations) . ' stations');
            
            
            if (empty($stations)) {
                $this->logger->info('No stations found for export');
                $this->addFlash('warning', 'No stations found matching the selected criteria');
                return $this->redirectToRoute('admin_bicycle_rentals', ['tab' => 'stations']);
            }
            
            $totalCapacity = 0;
            $totalBikes = 0;
            $totalChargingDocks = 0;
            $activeStations = 0;
            
            foreach ($stations as $station) {
                if ($station->getTotalDocks() === null) {
                    $this->logger->warning('Station #' . $station->getIdStation() . ' has null totalDocks, defaulting to 0');
                    $station->setTotalDocks(0);
                }
                
                if ($station->getAvailableBikes() === null) {
                    $this->logger->warning('Station #' . $station->getIdStation() . ' has null availableBikes, defaulting to 0');
                    $station->setAvailableBikes(0);
                }
                
                if ($station->getChargingBikes() === null) {
                    $this->logger->warning('Station #' . $station->getIdStation() . ' has null chargingBikes, defaulting to 0');
                    $station->setChargingBikes(0);
                }
                
                $totalCapacity += $station->getTotalDocks();
                $totalBikes += $station->getAvailableBikes();
                $totalChargingDocks += $station->getChargingBikes();
                
                if ($station->getStatus() === BICYCLE_STATION_STATUS::ACTIVE) {
                    $activeStations++;
                }
            }
            
            $stats = [
                'totalStations' => count($stations),
                'activeStations' => $activeStations,
                'totalCapacity' => $totalCapacity,
                'totalChargingDocks' => $totalChargingDocks,
                'avgOccupancy' => $totalCapacity > 0 ? ($totalBikes / $totalCapacity) * 100 : 0
            ];
            
            $this->logger->info('Calculating station activity data');
         
            $stationActivity = [];
            try {
                $rentalStats = $this->entityManager->getRepository(BicycleRental::class)
                    ->createQueryBuilder('r')
                    ->select('COUNT(r.id_user_rental) as rentalCount', 'ss.name as stationName', 'ss.id_station as stationId')
                    ->leftJoin('r.start_station', 'ss')
                    ->groupBy('ss.id_station')
                    ->orderBy('rentalCount', 'DESC')
                    ->getQuery()
                    ->getResult();
                    
                foreach ($rentalStats as $stat) {
                    $stationActivity[$stat['stationId']] = [
                        'name' => $stat['stationName'],
                        'rentalsStarted' => $stat['rentalCount'],
                        'rentalsEnded' => 0 // Will be populated below
                    ];
                }
                
                $returnStats = $this->entityManager->getRepository(BicycleRental::class)
                    ->createQueryBuilder('r')
                    ->select('COUNT(r.id_user_rental) as returnCount', 'es.name as stationName', 'es.id_station as stationId')
                    ->leftJoin('r.end_station', 'es')
                    ->groupBy('es.id_station')
                    ->orderBy('returnCount', 'DESC')
                    ->getQuery()
                    ->getResult();
                    
                foreach ($returnStats as $stat) {
                    if (isset($stationActivity[$stat['stationId']])) {
                        $stationActivity[$stat['stationId']]['rentalsEnded'] = $stat['returnCount'];
                    } else {
                        $stationActivity[$stat['stationId']] = [
                            'name' => $stat['stationName'],
                            'rentalsStarted' => 0,
                            'rentalsEnded' => $stat['returnCount']
                        ];
                    }
                }
                
                
                usort($stationActivity, function($a, $b) {
                    $totalA = $a['rentalsStarted'] + $a['rentalsEnded'];
                    $totalB = $b['rentalsStarted'] + $b['rentalsEnded'];
                    return $totalB <=> $totalA;
                });
            } catch (\Exception $e) {
                $this->logger->error('Error calculating station activity: ' . $e->getMessage(), [
                    'exception' => $e
                ]);
              
                $stationActivity = [];
            }
            
            $this->logger->info('Preparing data for export');
         
            $headers = [
                'ID', 'Station Name', 'Status', 'Available Bicycles', 
                'Available Docks', 'Total Docks', 'Occupancy Rate (%)', 'Location'
            ];
            
            $exportData = [];
            
            foreach ($stations as $station) {
           
                $occupancyRate = $station->getTotalDocks() > 0 
                    ? ($station->getAvailableBikes() / $station->getTotalDocks()) * 100 
                    : 0;
                
            
                try {
                    $statusLabel = match($station->getStatus()) {
                        BICYCLE_STATION_STATUS::ACTIVE => 'Active',
                        BICYCLE_STATION_STATUS::MAINTENANCE => 'Maintenance',
                        BICYCLE_STATION_STATUS::INACTIVE => 'Inactive',
                        default => $station->getStatus() ? ucfirst($station->getStatus()->value) : 'Unknown'
                    };
                } catch (\Exception $e) {
                    $this->logger->error('Error formatting status for station #' . $station->getIdStation() . ': ' . $e->getMessage());
                    $statusLabel = 'Unknown';
                }
                
        
                if ($station->getLocation() === null) {
                    $this->logger->warning('Station #' . $station->getIdStation() . ' has no location');
                    $location = 'No location';
                } else {
                    $location = $station->getLocation()->getAddress() ?: 'No address';
                }
                
               
                $exportData[] = [
                    $station->getIdStation(),
                    $station->getName() ?: 'Unnamed Station',
                    $statusLabel,
                    $station->getAvailableBikes(),
                    $station->getAvailableDocks(),
                    $station->getTotalDocks(),
                    round($occupancyRate, 1),
                    $location
                ];
            }
            
            $this->logger->info('Processed ' . count($exportData) . ' stations for export');
       
            $filters = [
                'status' => $status ? ucfirst($status) : ''
            ];
            
            $filename = 'stations-export-' . date('Y-m-d-H-i-s');
            
            $this->logger->info('Starting export generation in ' . $format . ' format');
            
        
            $response = null;
            
    
            switch ($format) {
                case 'excel':
                    $this->logger->info('Generating Excel export');
                    $columnStyles = [
                        3 => ['format' => NumberFormat::FORMAT_NUMBER],      
                        4 => ['format' => NumberFormat::FORMAT_NUMBER],    
                        5 => ['format' => NumberFormat::FORMAT_NUMBER],     
                        6 => ['format' => NumberFormat::FORMAT_PERCENTAGE_00], 
                    ];
                    
                    $response = $this->exportService->exportToExcel(
                        $headers, 
                        $exportData, 
                        $filename, 
                        $columnStyles,
                        'Bicycle Station Directory'
                    );
                    break;
                    
                case 'pdf':
                    $this->logger->info('Generating PDF export');
                    $response = $this->exportService->exportToPdf(
                        'back-office/export/stations-pdf.html.twig',
                        [
                            'stations' => $stations,
                            'stats' => $stats,
                            'filters' => $filters,
                            'stationActivity' => $stationActivity,
                            'title' => 'Bicycle Station Network Export'
                        ],
                        $filename
                    );
                    break;
                    
                case 'csv':
                default:
                    $this->logger->info('Generating CSV export');
                    $response = $this->exportService->exportToCsv(
                        $headers,
                        $exportData,
                        $filename
                    );
                    break;
            }
            
            $this->logger->info('Generated export response, preparing headers');
            
    
            $response->headers->set('Content-Description', 'File Transfer');
            $response->headers->set('Cache-Control', 'private');
            $response->headers->set('X-Accel-Buffering', 'no');
            
    
            while (ob_get_level()) {
                ob_end_clean();
            }
            
            $this->logger->info('Export complete, sending response');
            return $response;
            
        } catch (\Exception $e) {
        
            $this->logger->error('Station export error: ' . $e->getMessage(), [
                'exception_class' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'previous' => $e->getPrevious() ? $e->getPrevious()->getMessage() : null,
                'format' => $request->query->get('format', 'unknown')
            ]);
            
          
            $this->addFlash('error', 'Error creating station export: ' . $e->getMessage());
            return $this->redirectToRoute('admin_bicycle_rentals', ['tab' => 'stations']);
        }
    }

    #[Route('/{id}', name: 'admin_bicycle_station_detail')]
    public function stationDetail($id): Response
    {
       
        $id = (int) $id;
        
        $station = $this->stationService->getStation($id);

        if (!$station) {
            $this->addFlash('error', 'Station not found.');
            return $this->redirectToRoute('admin_bicycle_rentals', ['tab' => 'stations']);
        }

 
        $bicycles = $this->bicycleService->getBicyclesByStation($station);

       
        $rentals = $this->rentalService->getRentalsByStation($station);

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

       
        $statistics = [
            'totalRentals' => count($rentals),
            'availableBikes' => $station->getAvailableBikes(),
            'availableDocks' => $station->getAvailableDocks(),
            'totalDocks' => $station->getTotalDocks(),
            'occupancyRate' => $station->getTotalDocks() > 0
                ? ($station->getAvailableBikes() / $station->getTotalDocks()) * 100
                : 0,
        ];

        return $this->render('back-office/bicycle/Station/station-detail.html.twig', [
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