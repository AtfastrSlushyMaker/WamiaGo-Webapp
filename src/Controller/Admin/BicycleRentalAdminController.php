<?php

namespace App\Controller\Admin;

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
use App\Service\LocationService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Knp\Component\Pager\PaginatorInterface;

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

    public function __construct(
        EntityManagerInterface $entityManager,
        BicycleService $bicycleService,
        BicycleStationService $stationService,
        BicycleRentalService $rentalService,
        LoggerInterface $logger,
        PaginatorInterface $paginator,
        LocationService $locationService = null
    ) {
        $this->entityManager = $entityManager;
        $this->bicycleService = $bicycleService;
        $this->stationService = $stationService;
        $this->rentalService = $rentalService;
        $this->locationService = $locationService;
        $this->logger = $logger;
        $this->paginator = $paginator;
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
     * Main rentals listing page with filtering and pagination.
     */
    #[Route('/', name: 'admin_bicycle_rentals_index')]
    public function index(Request $request): Response
    {
        try {
            // Get query parameters for filtering
            $status = $request->query->get('status');
            $stationId = $request->query->get('station');
            $dateFrom = $request->query->get('dateFrom');
            $dateTo = $request->query->get('dateTo');
            $page = $request->query->getInt('page', 1);
            $perPage = $request->query->getInt('perPage', 10);
            
            // Create query builder for rentals
            $queryBuilder = $this->entityManager->createQueryBuilder()
                ->select('r')
                ->from(BicycleRental::class, 'r')
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
                        $queryBuilder->andWhere('r.start_time IS NULL')
                                    ->andWhere('r.end_time IS NULL');
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

            // Get paginated results - this is the key fix for pagination
            $rentals = $this->paginator->paginate(
                $queryBuilder->getQuery(),
                $page,
                $perPage,
                [
                    'defaultSortFieldName' => 'r.id_user_rental',
                    'defaultSortDirection' => 'DESC',
                ]
            );
            
            // Calculate statistics for the dashboard
            $allRentals = $this->rentalService->getAllRentals();
            
            $completedCount = 0;
            $activeCount = 0;
            $reservedCount = 0;
            
            foreach ($allRentals as $rental) {
                if ($rental->getEndTime()) {
                    $completedCount++;
                } elseif ($rental->getStartTime()) {
                    $activeCount++;
                } else {
                    $reservedCount++;
                }
            }
            
            // Get all bicycles to populate status cards
            $bicycles = $this->bicycleService->getAllBicycles();
            
            // Calculate counts for the status cards
            $availableCount = 0;
            $inUseCount = 0;
            $maintenanceCount = 0;
            $chargingCount = 0;
            
            foreach ($bicycles as $bicycle) {
                $status = $bicycle->getStatus()->value;
                if ($status === 'available') {
                    $availableCount++;
                } elseif ($status === 'in_use') {
                    $inUseCount++;
                } elseif ($status === 'maintenance') {
                    $maintenanceCount++;
                } elseif ($status === 'charging') {
                    $chargingCount++;
                }
            }
            
            // Create all necessary forms
            $forms = $this->createCommonForms();
            
            // Pass all needed template variables
            return $this->render('back-office/bicycle/rental.html.twig', array_merge([
                'rentals' => $rentals,
                'bicycles' => $bicycles,
                'stations' => $this->stationService->getAllStations(),
                'stats' => [
                    'totalRentals' => count($allRentals),
                    'completedCount' => $completedCount,
                    'activeCount' => $activeCount,
                    'reservedCount' => $reservedCount,
                    'completionRate' => count($allRentals) > 0 ? round(($completedCount / count($allRentals)) * 100) : 0,
                    'totalRevenue' => array_sum(array_map(function($r) { 
                        return $r->getCost() ?: 0; 
                    }, $allRentals))
                ],
                'filters' => [
                    'status' => $status,
                    'stationId' => $stationId,
                    'dateFrom' => $dateFrom,
                    'dateTo' => $dateTo
                ],
                'availableCount' => $availableCount,
                'inUseCount' => $inUseCount,
                'maintenanceCount' => $maintenanceCount,
                'chargingCount' => $chargingCount,
                'active_tab' => 'rentals',
                'pagination' => $rentals,  // Explicitly adding the pagination variable for the view
                'users' => $this->entityManager->getRepository(User::class)->findAll(),
                'rentalService' => $this->rentalService,
                'locationService' => $this->locationService,
                'bicycleService' => $this->bicycleService,
                'stationService' => $this->stationService
            ], $forms));
        } catch (\Exception $e) {
            $this->logger->error('Error loading rentals: ' . $e->getMessage());
            
            $forms = $this->createCommonForms();
            
            return $this->render('back-office/bicycle/rental.html.twig', array_merge([
                'rentals' => [],
                'stations' => $this->stationService->getAllStations(),
                'error' => $e->getMessage(),
                'active_tab' => 'rentals'
            ], $forms));
        }
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
        
        return $this->render('back-office/bicycle/rental-new.html.twig', array_merge([
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
        
        return $this->render('back-office/bicycle/rental-details.html.twig', array_merge([
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
        
        return $this->render('back-office/bicycle/rental-edit.html.twig', array_merge([
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
    #[Route('/export', name: 'admin_bicycle_rental_export', methods: ['GET'])]
    public function export(Request $request): Response
    {
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
        
        $rentals = $queryBuilder->getQuery()->getResult();
        
        // Generate export file according to format
        if ($format === 'excel') {
            // Excel export code would go here
            // For the moment, we'll just redirect to CSV
            return $this->redirectToRoute('admin_bicycle_rental_export', array_merge(
                $request->query->all(),
                ['format' => 'csv']
            ));
        }
        
        // Generate CSV content
        $csvData = "ID,User,Bicycle,Start Station,End Station,Start Time,End Time,Duration,Distance (km),Battery Used,Cost (TND),Status\n";
        
        foreach ($rentals as $rental) {
            $status = 'Reserved';
            if ($rental->getEndTime()) {
                $status = 'Completed';
            } elseif ($rental->getStartTime()) {
                $status = 'Active';
            }
            
            // Calculate duration for completed rentals
            $duration = '-';
            if ($rental->getStartTime() && $rental->getEndTime()) {
                $durationSeconds = $rental->getEndTime()->getTimestamp() - $rental->getStartTime()->getTimestamp();
                $hours = floor($durationSeconds / 3600);
                $minutes = floor(($durationSeconds % 3600) / 60);
                $duration = $hours . 'h ' . $minutes . 'm';
            }
            
            $csvData .= sprintf(
                '"%s","%s","%s","%s","%s","%s","%s","%s","%s","%s","%s","%s"' . "\n",
                'B' . str_pad($rental->getIdUserRental(), 5, '0', STR_PAD_LEFT),
                $rental->getUser() ? $rental->getUser()->getUsername() : 'Unknown',
                $rental->getBicycle() ? 'Bike #' . $rental->getBicycle()->getIdBike() : 'Unknown',
                $rental->getStartStation() ? $rental->getStartStation()->getName() : 'Unknown',
                $rental->getEndStation() ? $rental->getEndStation()->getName() : '-',
                $rental->getStartTime() ? $rental->getStartTime()->format('Y-m-d H:i') : '-',
                $rental->getEndTime() ? $rental->getEndTime()->format('Y-m-d H:i') : '-',
                $duration,
                $rental->getDistanceKm() ? $rental->getDistanceKm() : '-',
                $rental->getBatteryUsed() ? $rental->getBatteryUsed() : '-',
                $rental->getCost() ? number_format($rental->getCost(), 3) : '-',
                $status
            );
        }
        
        $response = new Response($csvData);
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="bicycle-rentals-export.csv"');
        
        return $response;
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