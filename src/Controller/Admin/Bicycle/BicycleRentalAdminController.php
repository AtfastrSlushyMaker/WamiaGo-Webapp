<?php

namespace App\Controller\Admin\Bicycle;

use App\Entity\Bicycle;
use App\Entity\BicycleStation;
use App\Entity\BicycleRental;
use App\Entity\User;
use App\Enum\BICYCLE_STATUS;
use App\Form\BicycleRentalType;
use App\Form\BicycleType;
use App\Service\BicycleService;
use App\Service\BicycleStationService;
use App\Service\BicycleRentalService;
use App\Service\Export\ExportService;
use App\Service\LocationService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Controller for managing bicycle rentals in the admin panel.
 */
#[Route('/admin/bicycle-rental')]
class BicycleRentalAdminController extends AbstractController
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
        LoggerInterface $logger,
        PaginatorInterface $paginator,
        ExportService $exportService,
        LocationService $locationService = null
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

    /**
     * Creates all the forms needed for the bicycle dashboard
     */
    private function createCommonForms(): array
    {
        // Create Add Bicycle Form
        $newBicycle = new Bicycle();
        $newBicycle->setLastUpdated(new \DateTime());
        $newBicycle->setStatus(\App\Enum\BICYCLE_STATUS::AVAILABLE);
        $addBicycleForm = $this->createForm(BicycleType::class, $newBicycle);
        
        // Create Edit Bicycle Form
        $editBicycle = new Bicycle();
        $editBicycleForm = $this->createForm(BicycleType::class, $editBicycle);
        
        // Create Station Assignment Form
        $stationAssignForm = $this->createFormBuilder()
            ->add('bicycles', EntityType::class, [
                'class' => Bicycle::class,
                'choice_label' => function(Bicycle $bicycle) {
                    return 'BIKE-' . sprintf('%04d', $bicycle->getIdBike());
                },
                'multiple' => true,
                'required' => true
            ])
            ->add('station', EntityType::class, [
                'class' => BicycleStation::class,
                'choice_label' => 'name',
                'required' => true
            ])
            ->getForm();
        
        // Create Maintenance Form
        $maintenanceForm = $this->createFormBuilder()
            ->add('bicycles', EntityType::class, [
                'class' => Bicycle::class,
                'choice_label' => function(Bicycle $bicycle) {
                    return 'BIKE-' . sprintf('%04d', $bicycle->getIdBike());
                },
                'multiple' => true,
                'required' => true
            ])
            ->add('maintenanceType', ChoiceType::class, [
                'choices' => [
                    'Routine Check' => 'routine',
                    'Repair' => 'repair',
                    'Battery Service' => 'battery',
                    'Software Update' => 'software'
                ],
                'required' => true
            ])
            ->add('scheduledDate', DateTimeType::class, [
                'widget' => 'single_text',
                'required' => true,
            ])
            ->add('priority', ChoiceType::class, [
                'choices' => [
                    'Low' => 'low',
                    'Medium' => 'medium',
                    'High' => 'high',
                    'Urgent' => 'urgent'
                ],
                'required' => true
            ])
            ->add('description', TextareaType::class, [
                'required' => false
            ])
            ->getForm();
            
        return [
            'addBicycleForm' => $addBicycleForm->createView(),
            'editBicycleForm' => $editBicycleForm->createView(),
            'stationAssignForm' => $stationAssignForm->createView(),
            'maintenanceForm' => $maintenanceForm->createView(),
        ];
    }

    /**
     * Create a new rental.
     */
    #[Route('/new', name: 'admin_bicycle_rental_new', methods: ['GET', 'POST'])]
    public function new(Request $request, ValidatorInterface $validator): Response
    {
        $rental = new BicycleRental();
        $form = $this->createForm(BicycleRentalType::class, $rental);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                // Get bicycle ID from request
                $bicycleId = $request->request->get('bicycleId');
                $bicycle = $this->bicycleService->getBicycle((int)$bicycleId);
                
                if (!$bicycle) {
                    throw new \Exception('Bicycle not found');
                }
                
                if ($bicycle->getStatus() !== BICYCLE_STATUS::AVAILABLE) {
                    throw new \Exception('This bicycle is not available for reservation');
                }
                
                // Set bicycle and start station
                $rental->setBicycle($bicycle);
                $rental->setStartStation($bicycle->getBicycleStation());
                
                // Validate the entity with our Assert constraints
                $errors = $validator->validate($rental);
                if (count($errors) > 0) {
                    $errorMessages = [];
                    foreach ($errors as $error) {
                        $errorMessages[] = $error->getPropertyPath() . ': ' . $error->getMessage();
                        $this->addFlash('error', $error->getPropertyPath() . ': ' . $error->getMessage());
                    }
                    throw new \Exception('Validation failed: ' . implode(', ', $errorMessages));
                }
                
                // Create the rental
                $this->rentalService->createBicycleRental($rental->getUser(), $rental);
                
                // Update bicycle status to reserved
                $bicycle->setStatus(BICYCLE_STATUS::RESERVED);
                $this->entityManager->flush();
                
                $this->addFlash('success', 'Rental created successfully');
                return $this->redirectToRoute('admin_bicycle_rental_show', ['id' => $rental->getIdUserRental()]);
            } catch (\Exception $e) {
                $this->logger->error('Error creating rental: ' . $e->getMessage());
                $this->addFlash('error', 'Error creating rental: ' . $e->getMessage());
            }
        }
        
        // Get users and stations for the form
        $users = $this->entityManager->getRepository(User::class)->findAll();
        $stations = $this->stationService->getAllStations();
        
        // Create all necessary forms
        $forms = $this->createCommonForms();
        
        return $this->render('back-office/bicycle/Rental/rental-new.html.twig', array_merge([
            'rental' => $rental,
            'form' => $form->createView(),
            'users' => $users,
            'stations' => $stations,
            'active_tab' => 'rentals'
        ], $forms));
    }

    /**
     * Show rental details.
     */
    #[Route('/{id}', name: 'admin_bicycle_rental_show', methods: ['GET'])]
    public function show($id): Response
    {
        $rental = $this->entityManager->getRepository(BicycleRental::class)->find((int)$id);
        
        if (!$rental) {
            $this->addFlash('error', 'Rental not found');
            return $this->redirectToRoute('admin_bicycle_rentals',["tab"=>"rentals"]);
        }
        
        // Create all necessary forms
        $forms = $this->createCommonForms();
        
        return $this->render('back-office/bicycle/Rental/rental-details.html.twig', array_merge([
            'rental' => $rental,
            'active_tab' => 'rentals'
        ], $forms));
    }

    /**
     * Edit a rental.
     */
    #[Route('/{id}/edit', name: 'admin_bicycle_rental_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, int $id, ValidatorInterface $validator): Response
    {
        $rental = $this->entityManager->getRepository(BicycleRental::class)->find($id);
        
        if (!$rental) {
            $this->addFlash('error', 'Rental not found');
            return $this->redirectToRoute('admin_bicycle_rentals_index');
        }
        
        $form = $this->createForm(BicycleRentalType::class, $rental);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                // Handle completing a rental
                if ($request->request->has('completeRental') && !$rental->getEndTime()) {
                    $endStationId = $request->request->get('endStationId');
                    $endStation = $this->stationService->getStation((int)$endStationId);
                    
                    if (!$endStation) {
                        throw new \Exception('End station not found');
                    }
                    
                    $distanceKm = (float)$request->request->get('distanceKm', 0);
                    $batteryUsed = (float)$request->request->get('batteryUsed', 0);
                    
                    // Set values for validation
                    $rental->setEndStation($endStation);
                    $rental->setEndTime(new \DateTime());
                    $rental->setDistanceKm($distanceKm);
                    $rental->setBatteryUsed($batteryUsed);
                    
                    // Validate using Assert constraints
                    $errors = $validator->validate($rental);
                    if (count($errors) > 0) {
                        $errorMessages = [];
                        foreach ($errors as $error) {
                            $errorMessages[] = $error->getPropertyPath() . ': ' . $error->getMessage();
                            $this->addFlash('error', $error->getPropertyPath() . ': ' . $error->getMessage());
                        }
                        throw new \Exception('Validation failed: ' . implode(', ', $errorMessages));
                    }
                    
                    // Complete the rental
                    $this->rentalService->completeRental($rental, $endStation, $distanceKm, $batteryUsed);
                    $this->addFlash('success', 'Rental completed successfully');
                } else {
                    // Standard rental update - validate using Assert constraints
                    $errors = $validator->validate($rental);
                    if (count($errors) > 0) {
                        $errorMessages = [];
                        foreach ($errors as $error) {
                            $errorMessages[] = $error->getPropertyPath() . ': ' . $error->getMessage();
                            $this->addFlash('error', $error->getPropertyPath() . ': ' . $error->getMessage());
                        }
                        throw new \Exception('Validation failed: ' . implode(', ', $errorMessages));
                    }
                    
                    // Standard rental update
                    $this->entityManager->flush();
                    $this->addFlash('success', 'Rental updated successfully');
                }
                
                return $this->redirectToRoute('admin_bicycle_rental_show', ['id' => $rental->getIdUserRental()]);
            } catch (\Exception $e) {
                $this->logger->error('Error updating rental: ' . $e->getMessage());
                $this->addFlash('error', 'Error updating rental: ' . $e->getMessage());
            }
        }
        
        $stations = $this->stationService->getAllStations();
        
        // Create all necessary forms
        $forms = $this->createCommonForms();
        
        return $this->render('back-office/bicycle/Rental/rental-edit.html.twig', array_merge([
            'rental' => $rental,
            'form' => $form->createView(),
            'stations' => $stations,
            'active_tab' => 'rentals'
        ], $forms));
    }

    /**
     * Cancel a rental.
     */
    #[Route('/{id}/cancel', name: 'admin_bicycle_rental_cancel', methods: ['POST'])]
    public function cancel(int $id): Response
    {
        $rental = $this->entityManager->getRepository(BicycleRental::class)->find($id);
        
        if (!$rental) {
            $this->addFlash('error', 'Rental not found');
            return $this->redirectToRoute('admin_bicycle_rentals_index');
        }
        
        try {
            if ($rental->getEndTime()) {
                throw new \Exception('Cannot cancel a completed rental');
            }
            
            // Process cancellation
            $this->rentalService->cancelRental($rental);
            $this->addFlash('success', 'Rental cancelled successfully');
        } catch (\Exception $e) {
            $this->logger->error('Error cancelling rental: ' . $e->getMessage());
            $this->addFlash('error', 'Error cancelling rental: ' . $e->getMessage());
        }
        
        return $this->redirectToRoute('admin_bicycle_rentals', ['tab' => "rentals"]);
    }

    /**
     * Activate a rental.
     */
    #[Route('/{id}/activate', name: 'admin_bicycle_rental_activate', methods: ['POST'])]
    public function activate(int $id): Response
    {
        $rental = $this->entityManager->getRepository(BicycleRental::class)->find($id);
        
        if (!$rental) {
            $this->addFlash('error', 'Rental not found');
            return $this->redirectToRoute('admin_bicycle_rentals', ['tab' => "rentals"]);
        }
        
        try {
            if ($rental->getStartTime()) {
                throw new \Exception('This rental has already been activated');
            }
            
            if ($rental->getEndTime()) {
                throw new \Exception('Cannot activate a completed rental');
            }
            
            // Set rental start time to now
            $rental->setStartTime(new \DateTime());
            
            // Update bicycle status to IN_USE
            $bicycle = $rental->getBicycle();
            $bicycle->setStatus(BICYCLE_STATUS::IN_USE);
            
            $this->entityManager->flush();
            $this->addFlash('success', 'Rental activated successfully');
        } catch (\Exception $e) {
            $this->logger->error('Error activating rental: ' . $e->getMessage());
            $this->addFlash('error', 'Error activating rental: ' . $e->getMessage());
        }
        
        return $this->redirectToRoute('admin_bicycle_rental_show', ['id' => $rental->getIdUserRental()]);
    }

    /**
     * Delete a rental.
     */
    #[Route('/{id}/delete', name: 'admin_bicycle_rental_delete', methods: ['POST'])]
    public function delete(int $id): Response
    {
        $rental = $this->entityManager->getRepository(BicycleRental::class)->find($id);
        
        if (!$rental) {
            $this->addFlash('error', 'Rental not found');
            return $this->redirectToRoute('admin_bicycle_rentals_index');
        }
        
        try {
            // If the rental is active, we need to make the bicycle available again
            if ($rental->getStartTime() && !$rental->getEndTime()) {
                $bicycle = $rental->getBicycle();
                if ($bicycle) {
                    $bicycle->setStatus(BICYCLE_STATUS::AVAILABLE);
                }
            }
            
            // Use the service method to delete the rental
            $this->rentalService->deleteBicycleRental($rental);
            
            $this->addFlash('success', 'Rental deleted successfully');
        } catch (\Exception $e) {
            $this->logger->error('Error deleting rental: ' . $e->getMessage());
            $this->addFlash('error', 'Error deleting rental: ' . $e->getMessage());
        }
        
        return $this->redirectToRoute('admin_bicycle_rentals');
    }

    /**
     * Get available bicycles at a station.
     */
    #[Route('/station/{id}/bicycles', name: 'admin_station_available_bicycles', methods: ['GET'])]
    public function getAvailableBicycles(int $id): JsonResponse
    {
        $station = $this->stationService->getStation($id);
        
        if (!$station) {
            return new JsonResponse(['error' => 'Station not found'], 404);
        }
        
        $bicycles = $this->bicycleService->getBicyclesByStation($station, true);
        $bicycleData = [];
        
        foreach ($bicycles as $bicycle) {
            $isPremium = $bicycle->getBatteryLevel() > 90;
            $bicycleData[] = [
                'id' => $bicycle->getIdBike(),
                'batteryLevel' => $bicycle->getBatteryLevel(),
                'rangeKm' => $bicycle->getRangeKm(),
                'type' => $isPremium ? 'Premium E-Bike' : 'Standard E-Bike',
                'hourlyRate' => $isPremium ? 5.00 : 3.50
            ];
        }
        
        return new JsonResponse($bicycleData);
    }

    /**
     * Export rentals data.
     */
    #[Route('/export', name: 'admin_bicycle_rental_export', methods: ['GET'], priority: 10)]
    public function export(Request $request): Response
    {
        try {
            // Add diagnostic log at the beginning
            $this->logger->info('Starting rental export process', [
                'format' => $request->query->get('format', 'csv'),
                'status' => $request->query->get('status')
            ]);
            
            // Get query parameters for filtering
            $status = $request->query->get('status');
            $stationId = $request->query->get('station');
            $dateFrom = $request->query->get('dateFrom');
            $dateTo = $request->query->get('dateTo');
            $format = $request->query->get('format', 'csv');
            
            // Create query builder for rentals with filters
            $queryBuilder = $this->entityManager->getRepository(BicycleRental::class)
                ->createQueryBuilder('r')
                ->leftJoin('r.bicycle', 'b')
                ->leftJoin('r.user', 'u')
                ->leftJoin('r.start_station', 'ss')
                ->leftJoin('r.end_station', 'es')
                ->orderBy('r.id_user_rental', 'DESC');
            
            // Apply filters
            if ($status) {
                switch($status) {
                    case 'active':
                        $queryBuilder->andWhere('r.start_time IS NOT NULL')
                                    ->andWhere('r.end_time IS NULL');
                        break;
                    case 'completed':
                        $queryBuilder->andWhere('r.end_time IS NOT NULL');
                        break;
                    case 'reserved':
                        $queryBuilder->andWhere('r.start_time IS NULL');
                        break;
                }
            }
            
            if ($stationId) {
                $queryBuilder->andWhere('ss.id_station = :stationId')
                            ->setParameter('stationId', $stationId);
            }
            
            if ($dateFrom) {
                $fromDate = new \DateTime($dateFrom);
                $queryBuilder->andWhere('r.start_time >= :fromDate')
                            ->setParameter('fromDate', $fromDate->format('Y-m-d 00:00:00'));
            }
            
            if ($dateTo) {
                $toDate = new \DateTime($dateTo);
                $queryBuilder->andWhere('r.start_time <= :toDate')
                            ->setParameter('toDate', $toDate->format('Y-m-d 23:59:59'));
            }
            
            $this->logger->info('Executing rental query');
            $rentals = $queryBuilder->getQuery()->getResult();
            $this->logger->info('Retrieved ' . count($rentals) . ' rentals');
            
            // Special handling for empty results
            if (empty($rentals)) {
                $this->logger->info('No rentals found for export');
                $this->addFlash('warning', 'No rentals found matching the selected criteria');
                return $this->redirectToRoute('admin_bicycle_rentals', ['tab' => 'rentals']);
            }
            
            // Get stats for PDF export
            $stats = [
                'totalRentals' => count($rentals),
                'completedCount' => 0,
                'activeCount' => 0,
                'reservedCount' => 0,
                'totalRevenue' => 0
            ];
            
            // Prepare data for export
            $headers = [
                'ID', 'Customer', 'Bicycle', 'Pick-up Station', 'Return Station', 
                'Start Time', 'End Time', 'Duration', 'Distance (km)', 
                'Battery Used', 'Cost (TND)', 'Status'
            ];
            
            $this->logger->info('Processing rental data for export');
            $exportData = [];
            
            // Initialize with zero to catch missing values
            foreach ($rentals as $index => $rental) {
                // Safety check for null objects
                if (!$rental) {
                    $this->logger->warning('Found null rental at index ' . $index);
                    continue;
                }
                
                // Set defaults for numeric fields that should never be null
                if ($rental->getDistance_km() === null) {
                    $rental->setDistance_km(0);
                    $this->logger->info('Fixed null distance for rental #' . $rental->getIdUserRental());
                }
                
                if ($rental->getBattery_used() === null) {
                    $rental->setBattery_used(0);
                    $this->logger->info('Fixed null battery used for rental #' . $rental->getIdUserRental());
                }
                
                if ($rental->getCost() === null) {
                    $rental->setCost(0);
                    $this->logger->info('Fixed null cost for rental #' . $rental->getIdUserRental());
                }
                
                // Determine status
                $rentalStatus = 'Reserved';
                if ($rental->getEndTime()) {
                    $rentalStatus = 'Completed';
                    $stats['completedCount']++;
                } elseif ($rental->getStartTime()) {
                    $rentalStatus = 'Active';
                    $stats['activeCount']++;
                } else {
                    $stats['reservedCount']++;
                }
                
                // Calculate duration for completed rentals
                $duration = '';
                if ($rental->getStartTime() && $rental->getEndTime()) {
                    try {
                        $durationSeconds = $rental->getEndTime()->getTimestamp() - $rental->getStartTime()->getTimestamp();
                        $hours = floor($durationSeconds / 3600);
                        $minutes = floor(($durationSeconds % 3600) / 60);
                        $duration = $hours . 'h ' . $minutes . 'm';
                    } catch (\Exception $e) {
                        $this->logger->error('Error calculating duration for rental #' . $rental->getIdUserRental() . ': ' . $e->getMessage());
                        $duration = '-';
                    }
                }
                
                // Add to total revenue
                if ($rental->getCost()) {
                    $stats['totalRevenue'] += $rental->getCost();
                }
                
                // Safe string access with fallback values
                $rentalId = 'B' . str_pad($rental->getIdUserRental(), 5, '0', STR_PAD_LEFT);
                
                // Safe access to related entities
                $user = $rental->getUser();
                $customerName = $user ? $user->getName() ?? 'Unknown' : 'Unknown';
                
                $bicycle = $rental->getBicycle();
                $bicycleName = $bicycle ? 'Bike #' . $bicycle->getIdBike() : 'Unknown';
                
                $startStation = $rental->getStartStation();
                $startStationName = $startStation ? $startStation->getName() : 'Unknown';
                
                $endStation = $rental->getEndStation();
                $endStationName = $endStation ? $endStation->getName() : '';
                
                $startTime = $rental->getStartTime() ? $rental->getStartTime()->format('Y-m-d H:i') : '';
                $endTime = $rental->getEndTime() ? $rental->getEndTime()->format('Y-m-d H:i') : '';
                
                // Safe numeric value handling - use 0 instead of null for numeric fields
                $distanceKm = $rental->getDistance_km();
                $batteryUsed = $rental->getBattery_used();
                $cost = $rental->getCost();
                
                // Add row to export data
                $exportData[] = [
                    $rentalId,
                    $customerName,
                    $bicycleName,
                    $startStationName,
                    $endStationName,
                    $startTime,
                    $endTime,
                    $duration,
                    $distanceKm,
                    $batteryUsed,
                    $cost,
                    $rentalStatus
                ];
            }
            
            $this->logger->info('Processed ' . count($exportData) . ' rentals for export');
            
            // Set filters context for PDF export
            $filters = [
                'status' => $status,
                'stationId' => $stationId,
                'dateFrom' => $dateFrom,
                'dateTo' => $dateTo
            ];
            
            $filename = 'bicycle-rentals-export-' . date('Y-m-d-H-i-s');
            
            $this->logger->info('Starting export generation in ' . $format . ' format');
            
            // Create response object directly to avoid memory issues
            $response = null;
            
            // Generate export file and create response
            switch ($format) {
                case 'excel':
                    $this->logger->info('Generating Excel export');
                    $columnStyles = [
                        8 => ['format' => NumberFormat::FORMAT_NUMBER_00],
                        9 => ['format' => NumberFormat::FORMAT_NUMBER_00],
                        10 => ['format' => NumberFormat::FORMAT_NUMBER_00],
                    ];
                    
                    $response = $this->exportService->exportToExcel(
                        $headers, 
                        $exportData, 
                        $filename, 
                        $columnStyles,
                        'Bicycle Rentals'
                    );
                    break;
                    
                case 'pdf':
                    $this->logger->info('Generating PDF export');
                    $response = $this->exportService->exportToPdf(
                        'back-office/export/rentals-pdf.html.twig',
                        [
                            'rentals' => $rentals,
                            'stats' => $stats,
                            'filters' => $filters,
                            'title' => 'Bicycle Rentals Export'
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
            
            $this->logger->info('Generated export, preparing response');
            
            // Add extra headers to force download
            $response->headers->set('Content-Description', 'File Transfer');
            $response->headers->set('Cache-Control', 'private');
            $response->headers->set('X-Accel-Buffering', 'no');
            
            // Explicitly turn off output buffering to prevent interference with headers
            while (ob_get_level()) {
                ob_end_clean();
            }
            
            $this->logger->info('Export complete, sending response');
            return $response;
        } catch (\Exception $e) {
            // Detailed error logging with full context
            $this->logger->error('Export error: ' . $e->getMessage(), [
                'exception_class' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'previous' => $e->getPrevious() ? $e->getPrevious()->getMessage() : null,
                'format' => $request->query->get('format', 'unknown')
            ]);
            
            // Add helpful message for the user
            $this->addFlash('error', 'Error creating export: ' . $e->getMessage());
            return $this->redirectToRoute('admin_bicycle_rentals', ['tab' => 'rentals']);
        }
    }
    
    /**
     * Update the Bicycle Dashboard to support the rentals tab.
     */
    #[Route('/dashboard', name: 'admin_bicycle_rental_dashboard')]
    public function dashboard(Request $request): Response 
    {
        // Forward all request parameters to maintain any filters
        $params = ['tab' => 'rentals'];
        $queryParams = $request->query->all();
        if (!empty($queryParams)) {
            $params = array_merge($params, $queryParams);
        }
        
        return $this->redirectToRoute('admin_bicycle_dashboard', $params);
    }

    #[Route('/{id}/details', name:'admin_bicycle_rental_details', methods: ['GET'])]
    public function rental_details(Request $request, int $id): Response
    {
        $rental = $this->entityManager->getRepository(BicycleRental::class)->find($id);
        
        if (!$rental) {
            $this->addFlash('error', 'Rental not found');
            return $this->redirectToRoute('admin_bicycle_rentals_index');
        }
        
        // Create all necessary forms
        $forms = $this->createCommonForms();
        
        return $this->render('back-office/bicycle/rental-details.html.twig', array_merge([
            'rental' => $rental,
            'active_tab' => 'rentals'
        ], $forms));
    }
}