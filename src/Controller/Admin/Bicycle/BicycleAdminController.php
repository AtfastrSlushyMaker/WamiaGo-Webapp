<?php

namespace App\Controller\Admin\Bicycle;

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
use App\Service\ExportService;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
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
            return $this->redirectToRoute('admin_bicycle_rentals', ['tab' => "bicycles"]);
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
            return $this->redirectToRoute('admin_bicycle_rentals', ['tab' =>"bicycles"]);
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
                    return $this->redirectToRoute('admin_bicycle_rentals', ['tab' => "bicycles"]);
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
    
        return $this->redirectToRoute('admin_bicycle_rentals', ['tab' => "bicycles"]);
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
        return $this->redirectToRoute('admin_bicycle_rentals', ['tab' => "bicycles"]);
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

    // API Routes - Only keeping the one that's actively used
    
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
    #[Route('/{id}/details', name: 'admin_bicycle_details', methods: ['GET'])]
    public function details(
        int $id, 
        EntityManagerInterface $em
    ): Response {
        $bicycle = $em->getRepository(Bicycle::class)->find($id);
        
        if (!$bicycle) {
            $this->addFlash('error', 'Bicycle not found.');
            return $this->redirectToRoute('admin_bicycle_rentals', ['tab' => 'bicycles']);
        }
        
        // Get rental history for this bicycle
        $rentalHistory = $em->getRepository(BicycleRental::class)->findBy(
            ['bicycle' => $bicycle],
            ['start_time' => 'DESC'],
            10 // Limit to last 10 rentals
        );
        
        // Get statistics
        $stats = [
            'totalRentals' => count($rentalHistory),
            'totalDistance' => 0,
            'totalRevenue' => 0,
            'totalRentalDuration' => 0,
            'avgRentalDuration' => 0,
        ];
        
        $totalDuration = 0;
        foreach ($rentalHistory as $rental) {
            $stats['totalDistance'] += $rental->getDistance_km() ?? 0;
            $stats['totalRevenue'] += $rental->getCost() ?? 0;
            
            if ($rental->getEnd_time() && $rental->getStart_time()) {
                $duration = $rental->getEnd_time()->getTimestamp() - $rental->getStart_time()->getTimestamp();
                $totalDuration += $duration;
            }
        }
        
        $stats['totalRentalDuration'] = $totalDuration;
        
        if ($stats['totalRentals'] > 0) {
            $stats['avgRentalDuration'] = round($totalDuration / $stats['totalRentals'] / 60); // in minutes
        }
        
        // Get all stations for map display
        $stations = $em->getRepository(BicycleStation::class)->findAll();
        
        // Get maintenance history (placeholder)
        $maintenanceHistory = [];
        
        return $this->render('back-office/bicycle/Bicycle/bicycle-details.html.twig', [
            'bicycle' => $bicycle,
            'rentalHistory' => $rentalHistory,
            'statistics' => $stats,
            'stations' => $stations,
            'maintenanceHistory' => $maintenanceHistory
        ]);
    }

    /**
     * Export bicycles data in various formats
     */
    #[Route('/export', name: 'admin_bicycle_export', methods: ['GET'])]
    public function export(Request $request): Response
    {
        // Get filter parameters
        $status = $request->query->get('status');
        $stationId = $request->query->get('station');
        $format = $request->query->get('format', 'csv');
        
        // Create query builder with filters
        $queryBuilder = $this->entityManager->getRepository(Bicycle::class)
            ->createQueryBuilder('b')
            ->leftJoin('b.bicycleStation', 's')
            ->orderBy('b.idBike', 'ASC');
        
        // Apply filters
        if ($status) {
            $queryBuilder->andWhere('b.status = :status')
                ->setParameter('status', BICYCLE_STATUS::from($status));
        }
        
        if ($stationId) {
            $queryBuilder->andWhere('s.idStation = :stationId')
                ->setParameter('stationId', $stationId);
        }
        
        // Get all bicycles matching the criteria
        $bicycles = $queryBuilder->getQuery()->getResult();
        
        // Calculate statistics for PDF export
        $stats = [
            'totalBicycles' => count($bicycles),
            'availableCount' => 0,
            'inUseCount' => 0,
            'maintenanceCount' => 0,
            'chargingCount' => 0
        ];
        
        $batteryDistribution = [
            'premium' => 0,  // 90-100%
            'good' => 0,     // 60-89%
            'medium' => 0,   // 30-59%
            'low' => 0       // 0-29%
        ];
        
        // Set up data for export
        $headers = [
            'ID', 'Status', 'Battery Level (%)', 'Range (km)', 'Station', 'Last Updated'
        ];
        
        $exportData = [];
        
        foreach ($bicycles as $bicycle) {
            // Update statistics counts
            $status = $bicycle->getStatus()->value;
            switch($status) {
                case 'available':
                    $stats['availableCount']++;
                    break;
                case 'in_use':
                    $stats['inUseCount']++;
                    break;
                case 'maintenance':
                    $stats['maintenanceCount']++;
                    break;
                case 'charging':
                    $stats['chargingCount']++;
                    break;
            }
            
            // Update battery distribution
            $batteryLevel = $bicycle->getBatteryLevel();
            if ($batteryLevel >= 90) {
                $batteryDistribution['premium']++;
            } elseif ($batteryLevel >= 60) {
                $batteryDistribution['good']++;
            } elseif ($batteryLevel >= 30) {
                $batteryDistribution['medium']++;
            } else {
                $batteryDistribution['low']++;
            }
            
            // Format status for display
            $statusLabel = ucfirst(str_replace('_', ' ', $status));
            
            // Add row to export data
            $exportData[] = [
                $bicycle->getIdBike(),
                $statusLabel,
                $bicycle->getBatteryLevel(),
                $bicycle->getRangeKm(),
                $bicycle->getBicycleStation() ? $bicycle->getBicycleStation()->getName() : '-',
                $bicycle->getLastUpdated()->format('Y-m-d H:i')
            ];
        }
        
        // Set filters context for PDF export
        $filters = [
            'status' => $status ? ucfirst($status) : '',
            'stationId' => $stationId
        ];
        
        // Export based on requested format
        switch ($format) {
            case 'excel':
                $columnStyles = [
                    2 => ['format' => NumberFormat::FORMAT_PERCENTAGE_00], // Battery Level column
                    3 => ['format' => NumberFormat::FORMAT_NUMBER_00],     // Range column
                ];
                
                return $this->exportService->exportToExcel(
                    $headers, 
                    $exportData, 
                    'bicycles-export', 
                    $columnStyles,
                    'Bicycle Inventory'
                );
                
            case 'pdf':
                return $this->exportService->exportToPdf(
                    'back-office/export/bicycles-pdf.html.twig',
                    [
                        'bicycles' => $bicycles,
                        'stats' => $stats,
                        'filters' => $filters,
                        'batteryDistribution' => $batteryDistribution,
                        'title' => 'Bicycle Inventory Export'
                    ],
                    'bicycles-export'
                );
                
            case 'csv':
            default:
                return $this->exportService->exportToCsv(
                    $headers,
                    $exportData,
                    'bicycles-export'
                );
        }
    }
}