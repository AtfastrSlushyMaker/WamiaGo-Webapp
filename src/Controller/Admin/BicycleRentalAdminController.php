<?php

namespace App\Controller\Admin;

use App\Entity\Bicycle;
use App\Entity\BicycleStation;
use App\Entity\BicycleRental;
use App\Entity\User;
use App\Enum\BICYCLE_STATUS;
use App\Form\BicycleRentalType;
use App\Service\BicycleService;
use App\Service\BicycleStationService;
use App\Service\BicycleRentalService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Knp\Component\Pager\PaginatorInterface;

//#[IsGranted('ROLE_ADMIN')]
#[Route('/admin/bicycle-rental')]
class BicycleRentalAdminController extends AbstractController
{
    private $entityManager;
    private $bicycleService;
    private $stationService;
    private $rentalService;
    private $logger;
    private $paginator;

    public function __construct(
        EntityManagerInterface $entityManager,
        BicycleService $bicycleService,
        BicycleStationService $stationService,
        BicycleRentalService $rentalService,
        LoggerInterface $logger,
        PaginatorInterface $paginator
    ) {
        $this->entityManager = $entityManager;
        $this->bicycleService = $bicycleService;
        $this->stationService = $stationService;
        $this->rentalService = $rentalService;
        $this->logger = $logger;
        $this->paginator = $paginator;
    }

    #[Route('/', name: 'admin_bicycle_rentals_index')]
    public function index(Request $request): Response
    {
        // Get query parameters for filtering
        $status = $request->query->get('status');
        $stationId = $request->query->get('station');
        $dateFrom = $request->query->get('dateFrom');
        $dateTo = $request->query->get('dateTo');
        $page = $request->query->getInt('page', 1);
        
        // Add debug information about database connection and table
        $conn = $this->entityManager->getConnection();
        $tableExists = false;
        $recordCount = 0;
        $columns = [];
        $sampleData = [];
        
        try {
            // Check if the bicycle_rental table exists and has records
            $stmt = $conn->prepare("SHOW TABLES LIKE 'bicycle_rental'");
            $resultSet = $stmt->executeQuery();
            $tableExists = count($resultSet->fetchAllAssociative()) > 0;
            
            if ($tableExists) {
                // Count records directly from the database
                $stmt = $conn->prepare("SELECT COUNT(*) as count FROM bicycle_rental");
                $result = $stmt->executeQuery()->fetchAssociative();
                $recordCount = $result['count'];
                
                // Get column information
                $stmt = $conn->prepare("SHOW COLUMNS FROM bicycle_rental");
                $columnsResult = $stmt->executeQuery()->fetchAllAssociative();
                foreach ($columnsResult as $column) {
                    $columns[] = $column['Field'];
                }
                
                // Get sample data directly from the database
                if ($recordCount > 0) {
                    $stmt = $conn->prepare("SELECT * FROM bicycle_rental LIMIT 3");
                    $sampleData = $stmt->executeQuery()->fetchAllAssociative();
                }
            }
        } catch (\Exception $e) {
            $dbError = $e->getMessage();
        }
        
        // Try both methods of getting rentals to see which works
        $allRentalsRepo = [];
        $allRentalsDQL = [];
        
        try {
            // Method 1: Repository findAll()
            $allRentalsRepo = $this->entityManager->getRepository(BicycleRental::class)->findAllRentalsForAdmin();
        } catch (\Exception $e) {
            $repoError = $e->getMessage();
        }
        
        try {
            // Method 2: Direct DQL query
            $dql = "SELECT r FROM App\Entity\BicycleRental r";
            $query = $this->entityManager->createQuery($dql);
            $allRentalsDQL = $query->getResult();
        } catch (\Exception $e) {
            $dqlError = $e->getMessage();
        }
        
        // Use the first successful method for fetching rentals
        $allRentals = count($allRentalsRepo) > 0 ? $allRentalsRepo : $allRentalsDQL;
        
        // Create query builder for filtered rentals
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('r')
            ->from(BicycleRental::class, 'r')
            ->orderBy('r.id_user_rental', 'DESC');
        
        // Apply filters if provided
        if ($status) {
            switch($status) {
                case 'active':
                    $qb->andWhere('r.start_time IS NOT NULL')
                       ->andWhere('r.end_time IS NULL');
                    break;
                case 'completed':
                    $qb->andWhere('r.end_time IS NOT NULL');
                    break;
                case 'reserved':
                    $qb->andWhere('r.start_time IS NULL');
                    break;
            }
        }
        
        if ($stationId) {
            $qb->leftJoin('r.start_station', 'ss')
               ->andWhere('ss.id_station = :stationId')
               ->setParameter('stationId', $stationId);
        }
        
        if ($dateFrom) {
            $fromDate = new \DateTime($dateFrom);
            $qb->andWhere('r.start_time >= :fromDate')
               ->setParameter('fromDate', $fromDate->format('Y-m-d 00:00:00'));
        }
        
        if ($dateTo) {
            $toDate = new \DateTime($dateTo);
            $qb->andWhere('r.start_time <= :toDate')
               ->setParameter('toDate', $toDate->format('Y-m-d 23:59:59'));
        }
        
        try {
            // Execute the query with a timeout to prevent hanging
            $filteredRentals = $qb->getQuery()->setMaxResults(100)->getResult();
        } catch (\Exception $e) {
            $queryError = $e->getMessage();
            $filteredRentals = [];
        }
        
        // Fallback to direct SQL if Doctrine methods fail
        if (empty($allRentals) && empty($filteredRentals) && $recordCount > 0) {
            try {
                $sql = "SELECT * FROM bicycle_rental ORDER BY id_user_rental DESC LIMIT 100";
                $stmt = $conn->prepare($sql);
                $rawRentals = $stmt->executeQuery()->fetchAllAssociative();
                
                // Convert raw data to simple objects for template
                $allRentals = array_map(function($data) {
                    $rental = new \stdClass();
                    foreach ($data as $key => $value) {
                        $rental->$key = $value;
                    }
                    // Add methods required by template
                    $rental->getIdUserRental = function() use ($rental) { return $rental->id_user_rental; };
                    $rental->getStart_time = function() use ($rental) { 
                        return $rental->start_time ? new \DateTime($rental->start_time) : null; 
                    };
                    $rental->getEnd_time = function() use ($rental) { 
                        return $rental->end_time ? new \DateTime($rental->end_time) : null; 
                    };
                    $rental->getCost = function() use ($rental) { return $rental->cost; };
                    return $rental;
                }, $rawRentals);
                
                $filteredRentals = $allRentals;
            } catch (\Exception $e) {
                $sqlError = $e->getMessage();
            }
        }
        
        // Use the filtered rentals if filters were applied, otherwise use all rentals
        $rentals = ($status || $stationId || $dateFrom || $dateTo) ? $filteredRentals : $allRentals;
        
        // Count statistics
        $totalRentals = count($rentals);
        $completedCount = 0;
        $activeCount = 0;
        $reservedCount = 0;
        
        foreach ($rentals as $rental) {
            if (is_object($rental) && method_exists($rental, 'getEnd_time') && $rental->getEnd_time()) {
                $completedCount++;
            } elseif (is_object($rental) && method_exists($rental, 'getStart_time') && $rental->getStart_time()) {
                $activeCount++;
            } else {
                $reservedCount++;
            }
        }
        
        // Create the rental form
        $rental = new BicycleRental();
        $form = $this->createForm(BicycleRentalType::class, $rental);
        
        // Get all stations for filter dropdown
        $stations = $this->stationService->getAllStations();
        
        // Enhanced debug information
        $debug = [
            'table_exists' => $tableExists,
            'record_count' => $recordCount,
            'columns' => $columns,
            'sample_data' => $sampleData,
            'total_count_repo' => count($allRentalsRepo),
            'total_count_dql' => count($allRentalsDQL),
            'filtered_count' => count($filteredRentals),
            'final_count' => count($rentals),
            'is_array' => is_array($rentals),
            'class_type' => is_object($rentals[0] ?? null) ? get_class($rentals[0]) : 'N/A'
        ];
        
        if (isset($dbError)) $debug['error'] = $dbError;
        if (isset($repoError)) $debug['repo_error'] = $repoError;
        if (isset($dqlError)) $debug['dql_error'] = $dqlError;
        if (isset($queryError)) $debug['query_error'] = $queryError;
        if (isset($sqlError)) $debug['sql_error'] = $sqlError;
        
        return $this->render('back-office/bicycle/rental.html.twig', [
            'rentals' => $rentals,
            'stations' => $stations,
            'form' => $form->createView(),
            'debugInfo' => $debug,
            'stats' => [
                'totalRentals' => $totalRentals,
                'completedCount' => $completedCount,
                'activeCount' => $activeCount,
                'reservedCount' => $reservedCount,
                'completionRate' => $totalRentals > 0 ? round(($completedCount / $totalRentals) * 100) : 0,
                'totalRevenue' => array_sum(array_map(function($r) { 
                    return (is_object($r) && method_exists($r, 'getCost')) ? ($r->getCost() ?: 0) : 0; 
                }, $rentals))
            ],
            'filters' => [
                'status' => $status,
                'stationId' => $stationId,
                'dateFrom' => $dateFrom,
                'dateTo' => $dateTo
            ]
        ]);
    }

    #[Route('/new', name: 'admin_bicycle_rental_new', methods: ['GET', 'POST'])]
    public function new(Request $request, ValidatorInterface $validator): Response
    {
        $rental = new BicycleRental();
        $form = $this->createForm(BicycleRentalType::class, $rental);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                // Handle manually for non-form fields
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
                
                // Create the rental
                $this->rentalService->createBicycleRental($rental->getUser(), $rental);
                
                // Update bicycle status
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
        
        return $this->render('back-office/bicycle/rental-new.html.twig', [
            'rental' => $rental,
            'form' => $form->createView(),
            'users' => $users,
            'stations' => $stations
        ]);
    }

    #[Route('/{id}', name: 'admin_bicycle_rental_show', methods: ['GET'])]
    public function show(int $id): Response
    {
        $rental = $this->entityManager->getRepository(BicycleRental::class)->find($id);
        
        if (!$rental) {
            $this->addFlash('error', 'Rental not found');
            return $this->redirectToRoute('admin_bicycle_rentals_index');
        }
        
        return $this->render('back-office/bicycle/rental-details.html.twig', [
            'rental' => $rental
        ]);
    }

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
                // Handle special actions
                if ($request->request->has('completeRental') && !$rental->getEndTime()) {
                    $endStationId = $request->request->get('endStationId');
                    $endStation = $this->stationService->getStation((int)$endStationId);
                    
                    if (!$endStation) {
                        throw new \Exception('End station not found');
                    }
                    
                    $distanceKm = (float)$request->request->get('distanceKm', 0);
                    $batteryUsed = (float)$request->request->get('batteryUsed', 0);
                    
                    // Complete the rental
                    $this->rentalService->completeRental($rental, $endStation, $distanceKm, $batteryUsed);
                    $this->addFlash('success', 'Rental completed successfully');
                } else {
                    // Just update the rental
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
        
        return $this->render('back-office/bicycle/rental-edit.html.twig', [
            'rental' => $rental,
            'form' => $form->createView(),
            'stations' => $stations
        ]);
    }

    #[Route('/{id}/cancel', name: 'admin_bicycle_rental_cancel', methods: ['POST'])]
    public function cancel(Request $request, int $id): Response
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
        
        return $this->redirectToRoute('admin_bicycle_rentals_index');
    }

    #[Route('/{id}/activate', name: 'admin_bicycle_rental_activate', methods: ['POST'])]
    public function activate(Request $request, int $id): Response
    {
        $rental = $this->entityManager->getRepository(BicycleRental::class)->find($id);
        
        if (!$rental) {
            $this->addFlash('error', 'Rental not found');
            return $this->redirectToRoute('admin_bicycle_rentals_index');
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

    #[Route('/export', name: 'admin_bicycle_rental_export', methods: ['GET'])]
    public function export(Request $request): Response
    {
        // Get query parameters for filtering
        $status = $request->query->get('status');
        $stationId = $request->query->get('station');
        $dateFrom = $request->query->get('dateFrom');
        $dateTo = $request->query->get('dateTo');
        
        // Create query builder for rentals with the same filters as index
        $queryBuilder = $this->entityManager->getRepository(BicycleRental::class)
            ->createQueryBuilder('r')
            ->leftJoin('r.bicycle', 'b')
            ->leftJoin('r.user', 'u')
            ->leftJoin('r.start_station', 'ss')
            ->leftJoin('r.end_station', 'es')
            ->orderBy('r.id_user_rental', 'DESC');
        
        // Apply the same filters as in index
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
        
        $rentals = $queryBuilder->getQuery()->getResult();
        
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
}