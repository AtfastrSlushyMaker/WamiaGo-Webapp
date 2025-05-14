<?php

namespace App\Controller\Admin;

use App\Entity\Trip;
use App\Repository\TripRepository;
use App\Service\TripService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\BicycleRentalService;
use App\Service\BicycleStationService;
use App\Service\BicycleService;
use App\Form\BicycleType;
use App\Form\BicycleStationType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Knp\Component\Pager\PaginatorInterface;

class AdminController extends AbstractController
{
    private $bicycleRentalService;
    private $bicycleStationService;
    private $bicycleService;
    private $formFactory;
    private $entityManager;
    private $paginator;

    public function __construct(
        BicycleRentalService $bicycleRentalService,
        BicycleStationService $bicycleStationService,
        BicycleService $bicycleService,
        FormFactoryInterface $formFactory,
        EntityManagerInterface $entityManager,
        PaginatorInterface $paginator
    ) {
        $this->bicycleRentalService = $bicycleRentalService;
        $this->bicycleStationService = $bicycleStationService;
        $this->bicycleService = $bicycleService;
        $this->formFactory = $formFactory;
        $this->entityManager = $entityManager;
        $this->paginator = $paginator;
    }

    #[Route('/admin', name: 'admin_dashboard')]
    public function dashboard(): Response
    {
        $stats = [
            'rideShares' => 150,
            'taxiBookings' => 53,
            'bicycleRentals' => 44,
            'relocationBookings' => 65,
        ];

        return $this->render('back-office/dashboard.html.twig', [
            'stats' => $stats
        ]);
    }

    #[Route('/admin/users', name: 'admin_users')]
    public function users(Request $request): Response
    {
        $isAjax = $request->headers->get('X-Requested-With') === 'XMLHttpRequest';
        
        $role = $request->query->get('role');
        $status = $request->query->get('status');
        $verified = $request->query->get('verified');
        $searchTerm = $request->query->get('search');
        
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('u')
            ->from(\App\Entity\User::class, 'u')
            ->orderBy('u.id_user', 'DESC');
        
        if ($role) {
            $queryBuilder->andWhere('u.role = :role')
                        ->setParameter('role', $role);
        }
        
        if ($status) {
            $queryBuilder->andWhere('u.status = :status')
                        ->setParameter('status', $status);
        }
        
        if ($verified !== null) {
            $queryBuilder->andWhere('u.isVerified = :verified')
                        ->setParameter('verified', $verified == '1');
        }
        
        if ($searchTerm) {
            $queryBuilder->andWhere('u.name LIKE :search OR u.email LIKE :search OR u.phoneNumber LIKE :search')
                        ->setParameter('search', '%' . $searchTerm . '%');
        }
        
        $users = $this->paginator->paginate(
            $queryBuilder->getQuery(),
            $request->query->getInt('page', 1),
            $request->query->getInt('perPage', 10)
        );
        
        $allUsers = $this->entityManager->getRepository(\App\Entity\User::class)->findAll();
        $adminCount = 0;
        $clientCount = 0;
        $activeCount = 0;
        $suspendedCount = 0;
        $bannedCount = 0;
        $verifiedCount = 0;
        
        foreach ($allUsers as $user) {
            if ($user->getRole() === \App\Enum\ROLE::ADMIN) {
                $adminCount++;
            } elseif ($user->getRole() === \App\Enum\ROLE::CLIENT) {
                $clientCount++;
            }
            
            $status = $user->getStatus();
            if ($status === \App\Enum\ACCOUNT_STATUS::ACTIVE) {
                $activeCount++;
            } elseif ($status === \App\Enum\ACCOUNT_STATUS::DEACTIVATED) {
                $suspendedCount++;
            } elseif ($status === \App\Enum\ACCOUNT_STATUS::BANNED) {
                $bannedCount++;
            }
            
            if ($user->isVerified()) {
                $verifiedCount++;
            }
        }
        
        // Create empty user for edit form (fixes the edit modal not found issue)
        $editUser = new \App\Entity\User();
        
        $params = [
            'users' => $users,
            'stats' => [
                'totalUsers' => count($allUsers),
                'adminCount' => $adminCount,
                'clientCount' => $clientCount,
                'activeCount' => $activeCount,
                'suspendedCount' => $suspendedCount,
                'bannedCount' => $bannedCount,
                'verifiedCount' => $verifiedCount,
                'verificationRate' => count($allUsers) > 0 ? round(($verifiedCount / count($allUsers)) * 100) : 0
            ],
            'filters' => [
                'role' => $role,
                'status' => $status,
                'verified' => $verified,
                'search' => $searchTerm
            ],
            'account_statuses' => [
                ['value' => \App\Enum\ACCOUNT_STATUS::ACTIVE->value],
                ['value' => \App\Enum\ACCOUNT_STATUS::DEACTIVATED->value],
                ['value' => \App\Enum\ACCOUNT_STATUS::BANNED->value]
            ],
            'roles' => [
                ['value' => \App\Enum\ROLE::ADMIN->value],
                ['value' => \App\Enum\ROLE::CLIENT->value]
            ],
            'form_edit' => $editUser  // Add this variable for the edit modal
        ];
        
        if ($isAjax) {
            return $this->render('back-office/users/users.html.twig', $params);
        }
        
        return $this->render('back-office/users/users.html.twig', $params);
    }

    #[Route('/admin/user/new', name: 'admin_user_new', methods: ['POST'])]
    public function newUser(Request $request): Response
    {
        $data = json_decode($request->getContent(), true);
        $user = new \App\Entity\User();
        
        try {
        
            $user->setName($data['name']);
            $user->setEmail($data['email']);
            
    
            $hashedPassword = password_hash($data['password'], PASSWORD_BCRYPT);
            $user->setPassword($hashedPassword);
            
         
            $user->setPhoneNumber($data['phone'] ?? null);
            $user->setRole(\App\Enum\ROLE::from($data['role']));
            $user->setStatus(\App\Enum\ACCOUNT_STATUS::from($data['status']));
            $user->setIsVerified(isset($data['verified']) && $data['verified']);

            $this->entityManager->persist($user);
            $this->entityManager->flush();
            
            return $this->json([
                'success' => true,
                'message' => 'User created successfully',
                'user' => [
                    'id' => $user->getId_user(),
                    'name' => $user->getName(),
                    'email' => $user->getEmail()
                ]
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Error creating user: ' . $e->getMessage()
            ], 400);
        }
    }
    
    #[Route('/admin/users/api', name: 'admin_users_api', methods: ['GET'])]
    public function usersApi(Request $request): Response
    {
        $page = $request->query->getInt('page', 1);
        $perPage = $request->query->getInt('perPage', 10);
        $role = $request->query->get('role');
        $status = $request->query->get('status');
        $verified = $request->query->get('verified');
        $search = $request->query->get('search');
        
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('u')
            ->from(\App\Entity\User::class, 'u')
            ->orderBy('u.id_user', 'DESC');
        
        // Apply filters
        if ($role) {
            $queryBuilder->andWhere('u.role = :role')
                        ->setParameter('role', $role);
        }
        
        if ($status) {
            $queryBuilder->andWhere('u.status = :status')
                        ->setParameter('status', $status);
        }
        
        if ($verified !== null) {
            $queryBuilder->andWhere('u.isVerified = :verified')
                        ->setParameter('verified', $verified == '1');
        }
        
        if ($search) {
            $queryBuilder->andWhere('u.name LIKE :search OR u.email LIKE :search OR u.phoneNumber LIKE :search')
                        ->setParameter('search', '%' . $search . '%');
        }
        
        $paginator = $this->paginator->paginate(
            $queryBuilder->getQuery(),
            $page,
            $perPage
        );
        
        $users = [];
        foreach ($paginator->getItems() as $user) {
            $users[] = [
                'id' => $user->getId_user(),
                'name' => $user->getName(),
                'email' => $user->getEmail(),
                'phone' => $user->getPhoneNumber(),
                'role' => $user->getRole()->value,
                'status' => $user->getStatus()->value,
                'verified' => $user->isVerified(),
                'dateOfBirth' => $user->getDateOfBirth() ? $user->getDateOfBirth()->format('Y-m-d') : null,
                'profilePicture' => $user->getProfilePicture()
            ];
        }
        
        return $this->json([
            'users' => $users,
            'pagination' => [
                'total' => $paginator->getTotalItemCount(),
                'count' => count($users),
                'perPage' => $perPage,
                'currentPage' => $page,
                'lastPage' => ceil($paginator->getTotalItemCount() / $perPage)
            ]
        ]);
    }
    
    #[Route('/admin/api/user/{id}', name: 'admin_api_user_get', methods: ['GET'])]
    public function getUserApi(int $id): Response
    {
        $user = $this->entityManager->getRepository(\App\Entity\User::class)->find($id);
        
        if (!$user) {
            return $this->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }
        
        return $this->json([
            'success' => true,
            'user' => [
                'id' => $user->getId_user(),
                'name' => $user->getName(),
                'email' => $user->getEmail(),
                'phone' => $user->getPhoneNumber(),
                'role' => $user->getRole()->value,
                'status' => $user->getStatus()->value,
                'verified' => $user->isVerified(),
                'dateOfBirth' => $user->getDateOfBirth() ? $user->getDateOfBirth()->format('Y-m-d') : null,
                'profilePicture' => $user->getProfilePicture()
            ]
        ]);
    }
    
    #[Route('/admin/user/{id}/edit', name: 'admin_user_edit', methods: ['GET', 'POST'])]
    public function editUser(int $id, Request $request): Response
    {
        $user = $this->entityManager->getRepository(\App\Entity\User::class)->find($id);
        
        if (!$user) {
            return $this->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }
        
        // Handle GET request - return form data
        if ($request->isMethod('GET')) {
            return $this->json([
                'success' => true,
                'user' => [
                    'id' => $user->getId_user(),
                    'name' => $user->getName(),
                    'email' => $user->getEmail(),
                    'phone' => $user->getPhoneNumber(),
                    'role' => $user->getRole()->value,
                    'status' => $user->getStatus()->value,
                    'verified' => $user->isVerified(),
                    'dateOfBirth' => $user->getDateOfBirth() ? $user->getDateOfBirth()->format('Y-m-d') : null,
                    'profilePicture' => $user->getProfilePicture()
                ]
            ]);
        }
        
        // Handle POST request - update user
        $data = json_decode($request->getContent(), true);
        
        try {
            // Update user properties
            $user->setName($data['name']);
            $user->setEmail($data['email']);
            
            // Optional password update
            if (!empty($data['password'])) {
                $hashedPassword = password_hash($data['password'], PASSWORD_BCRYPT);
                $user->setPassword($hashedPassword);
            }
            
            // Set other properties
            $user->setPhoneNumber($data['phone'] ?? null);
            if (isset($data['role'])) {
                $user->setRole(\App\Enum\ROLE::from($data['role']));
            }
            if (isset($data['status'])) {
                $user->setStatus(\App\Enum\ACCOUNT_STATUS::from($data['status']));
            }
            if (isset($data['verified'])) {
                $user->setIsVerified($data['verified']);
            }
            
            // Save to database
            $this->entityManager->flush();
            
            return $this->json([
                'success' => true,
                'message' => 'User updated successfully',
                'user' => [
                    'id' => $user->getId_user(),
                    'name' => $user->getName(),
                    'email' => $user->getEmail()
                ]
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Error updating user: ' . $e->getMessage()
            ], 400);
        }
    }
    
    #[Route('/admin/user/{id}/delete', name: 'admin_user_delete', methods: ['POST', 'DELETE'])]
    public function deleteUser(int $id): Response
    {
        $user = $this->entityManager->getRepository(\App\Entity\User::class)->find($id);
        
        if (!$user) {
            return $this->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }
        
        try {
            $this->entityManager->remove($user);
            $this->entityManager->flush();
            
            return $this->json([
                'success' => true,
                'message' => 'User deleted successfully'
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Error deleting user: ' . $e->getMessage()
            ], 400);
        }
    }

    #[Route('/admin/ride-sharing', name: 'admin_ride_sharing')]
    public function rideSharing(EntityManagerInterface $entityManager): Response
    {
        // Fetch all trips from the database
        $trips = $entityManager->getRepository(Trip::class)->findAll();

        // Pass the trips to the template
        return $this->render('back-office/ride-sharing.html.twig', [
            'trips' => $trips,
        ]);
    }
    #[Route('/admin/trips/chart', name: 'admin_trips_chart')]
    public function tripsChart(TripRepository $tripRepository): Response
    {
        // Récupérer les données des trips groupées par mois
        $tripsByMonth = $tripRepository->countTripsByMonth();

        // Préparer les données pour le graphique
        $labels = [];
        $data = [];
        foreach ($tripsByMonth as $month => $count) {
            $labels[] = $month;
            $data[] = $count;
        }

        return $this->render('back-office/trips_chart.twig', [
            'labels' => json_encode($labels),
            'data' => json_encode($data),
        ]);
    }
#[Route('/admin/trips/pdf', name: 'admin_trips_pdf')]
public function generateAllTripsPdf(TripRepository $tripRepository): Response
{
    $trips = $tripRepository->findAll();

    if (empty($trips)) {
        $this->addFlash('error', 'No trips found.');
        return $this->redirectToRoute('admin_ride_sharing');
    }

    // Generate the PDF content
    $html = $this->renderView('back-office/pdftrip.twig', [
        'trips' => $trips,
    ]);

    $pdf = new \Dompdf\Dompdf();
    $pdf->loadHtml($html);
    $pdf->setPaper('A4', 'landscape');
    $pdf->render();

    // Return the PDF as a response
    return new Response($pdf->output(), 200, [
        'Content-Type' => 'application/pdf',
        'Content-Disposition' => 'inline; filename="all_trips.pdf"',
    ]);
}
// src/Controller/TripController.php

    #[Route('/admin/trip/delete/{id}', name: 'admin_trip_delete', methods: ['POST'])]
    public function deleteTrip(int $id, EntityManagerInterface $entityManager, TripService $tripService): Response
    {
        $trip = $entityManager->getRepository(Trip::class)->find($id);

        if (!$trip) {
            $this->addFlash('error', 'Trip not found.');
            return $this->redirectToRoute('admin_ride_sharing');
        }

        $tripService->deleteTrip($trip);

        $this->addFlash('success', 'Trip deleted successfully.');
        return $this->redirectToRoute('admin_ride_sharing');
    }

    #[Route('/admin/taxi-bookings', name: 'admin_taxi_bookings')]
    public function taxiBookings(): Response
    {
        return $this->render('back-office/taxi-bookings.html.twig');
    }

    #[Route('/admin/bicycle-rentals', name: 'admin_bicycle_rentals')]
    public function bicycleRentals(Request $request): Response
    {
        $tab = $request->query->get('tab', 'rentals');
        $isAjax = $request->headers->get('X-Requested-With') === 'XMLHttpRequest';
        
        // Get bicycles query and paginate it
        $bicyclesQuery = $this->bicycleService->getAllBicyclesQuery();
        $bicycles = $this->paginator->paginate(
            $bicyclesQuery,
            $request->query->getInt('page', 1),
            $request->query->getInt('perPage', 10)
        );
        
        // Get stations query and paginate it
        $stationsQuery = $this->bicycleStationService->getAllStationsQuery();
        $stations = $this->paginator->paginate(
            $stationsQuery,
            $request->query->getInt('page', 1),
            $request->query->getInt('perPage', 10)
        );
        
        // Get rentals query and paginate it
        $rentalsQuery = $this->bicycleRentalService->getAllRentalsQuery();
        $rentals = $this->paginator->paginate(
            $rentalsQuery,
            $request->query->getInt('page', 1),
            $request->query->getInt('perPage', 10)
        );
        
        $users = $this->entityManager->getRepository(\App\Entity\User::class)->findAll();

        // Create Add/Edit Bicycle Forms
        $newBicycle = new \App\Entity\Bicycle();
        $newBicycle->setLastUpdated(new \DateTime());
        $newBicycle->setStatus(\App\Enum\BICYCLE_STATUS::AVAILABLE);
        $addBicycleForm = $this->formFactory->create(\App\Form\BicycleType::class, $newBicycle);
        $editBicycle = new \App\Entity\Bicycle();
        $editBicycleForm = $this->formFactory->create(BicycleType::class, $editBicycle, ['bicycleId' => $newBicycle->getIdBike()]);

        // Create Station Form using BicycleStationType
        $newStation = new \App\Entity\BicycleStation();
        $newStation->setStatus(\App\Enum\BICYCLE_STATION_STATUS::ACTIVE);
        $newStation->setChargingBikes(0);
        $newStation->setTotalDocks(10);
        $newStation->setAvailableBikes(0);
        $newStation->setAvailableDocks(10);
        $stationForm = $this->formFactory->create(\App\Form\BicycleStationType::class, $newStation);

        $createStationForm = $this->formFactory->create(\App\Form\BicycleStationType::class, $newStation);

        $stationAssignForm = $this->formFactory->createBuilder()
            ->add('bicycles', \Symfony\Bridge\Doctrine\Form\Type\EntityType::class, [
                'class' => \App\Entity\Bicycle::class,
                'choice_label' => function($bicycle) { return 'BIKE-' . sprintf('%04d', $bicycle->getIdBike()); },
                'multiple' => true,
                'required' => true
            ])
            ->add('station', \Symfony\Bridge\Doctrine\Form\Type\EntityType::class, [
                'class' => \App\Entity\BicycleStation::class,
                'choice_label' => 'name',
                'required' => true
            ])
            ->getForm();
        $maintenanceForm = $this->formFactory->createBuilder()
            ->add('bicycles', \Symfony\Bridge\Doctrine\Form\Type\EntityType::class, [
                'class' => \App\Entity\Bicycle::class,
                'choice_label' => function($bicycle) { return 'BIKE-' . sprintf('%04d', $bicycle->getIdBike()); },
                'multiple' => true,
                'required' => true
            ])
            ->add('maintenanceType', \Symfony\Component\Form\Extension\Core\Type\ChoiceType::class, [
                'choices' => [
                    'Routine Check' => 'routine',
                    'Repair' => 'repair',
                    'Battery Service' => 'battery',
                    'Software Update' => 'software'
                ],
                'required' => true
            ])
            ->add('scheduledDate', \Symfony\Component\Form\Extension\Core\Type\DateTimeType::class, [
                'widget' => 'single_text',
                'required' => true,
            ])
            ->add('priority', \Symfony\Component\Form\Extension\Core\Type\ChoiceType::class, [
                'choices' => [
                    'Low' => 'low',
                    'Medium' => 'medium',
                    'High' => 'high',
                    'Urgent' => 'urgent'
                ],
                'required' => true
            ])
            ->add('description', \Symfony\Component\Form\Extension\Core\Type\TextareaType::class, [
                'required' => false
            ])
            ->getForm();

        // Create the query builder for rentals with filters and pagination
        $status = $request->query->get('status');
        $stationId = $request->query->get('station');
        $dateFrom = $request->query->get('dateFrom');
        $dateTo = $request->query->get('dateTo');
        $page = $request->query->getInt('page', 1);
        $perPage = $request->query->getInt('perPage', 10);
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('r')
            ->from(\App\Entity\BicycleRental::class, 'r')
            ->leftJoin('r.bicycle', 'b')
            ->leftJoin('r.user', 'u')
            ->leftJoin('r.start_station', 'ss')
            ->leftJoin('r.end_station', 'es')
            ->orderBy('r.id_user_rental', 'DESC');
        
        // Apply filters if provided
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
        
        // Paginate the rentals
        $rentals = $this->paginator->paginate(
            $queryBuilder->getQuery(),
            $request->query->getInt('page', 1),
            $request->query->getInt('perPage', 10)
        );

        // Stats
        $completedCount = 0;
        $activeCount = 0;
        $reservedCount = 0;
        foreach ($rentals as $rental) {
            if ($rental->getEndTime()) {
                $completedCount++;
            } elseif ($rental->getStartTime()) {
                $activeCount++;
            } else {
                $reservedCount++;
            }
        }
        
        // Get bicycle count by status
        $allBicycles = $this->bicycleService->getAllBicycles(); // Get all for stats calculation
        $availableCount = 0;
        $inUseCount = 0;
        $maintenanceCount = 0;
        $chargingCount = 0;
        foreach ($allBicycles as $bicycle) {
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

        // Station stats
        $stationCounts = $this->bicycleStationService->getStationCountsByStatus();
        $totalCapacity = $this->bicycleStationService->getTotalBicycleCapacity();
        $totalChargingDocks = $this->bicycleStationService->getTotalChargingDocks();
        $stationActivity = $this->bicycleStationService->getStationsWithRentalActivity(5);

        // Prepare view parameters
        $params = [
            'bicycles' => $bicycles,
            'stations' => $stations,
            'users' => $users,
            'rentals' => $rentals,
            'addBicycleForm' => $addBicycleForm->createView(),
            'editBicycleForm' => $editBicycleForm->createView(),
            'stationForm' => $stationForm->createView(),
            'createStationForm' => $createStationForm->createView(),
            'stationAssignForm' => $stationAssignForm->createView(),
            'maintenanceForm' => $maintenanceForm->createView(),
            'stats' => [
                'totalRentals' => $rentals->getTotalItemCount(),
                'completedCount' => $completedCount,
                'activeCount' => $activeCount,
                'reservedCount' => $reservedCount,
                'completionRate' => $rentals->getTotalItemCount() > 0 ? ($completedCount > 0 ? round(($completedCount / $rentals->getTotalItemCount()) * 100) : 0) : 0,
                'totalRevenue' => array_sum(array_map(function($r) { return $r->getCost() ?: 0; }, $rentals->getItems()))
            ],
            'availableCount' => $availableCount,
            'inUseCount' => $inUseCount,
            'maintenanceCount' => $maintenanceCount,
            'chargingCount' => $chargingCount,
            'stationCounts' => $stationCounts,
            'totalCapacity' => $totalCapacity,
            'totalChargingDocks' => $totalChargingDocks,
            'stationActivity' => $stationActivity,
            'filters' => [
                'status' => $status,
                'stationId' => $stationId,
                'dateFrom' => $dateFrom,
                'dateTo' => $dateTo
            ],
            'active_tab' => $tab,
            'bicycleService' => $this->bicycleService,
            'stationService' => $this->bicycleStationService,
            'rentalService' => $this->bicycleRentalService,
        ];

        // For AJAX requests, just return the specific tab content
        if ($isAjax) {
            return $this->render('back-office/bicycle-rentals.html.twig', $params);
        }

        return $this->render('back-office/bicycle-rentals.html.twig', $params);
    }

   /* #[Route('/admin/relocations', name: 'admin_relocations')]
    public function relocations(): Response
    {
        return $this->render('back-office/relocations.html.twig');
    }*/

    #[Route('/admin/settings', name: 'admin_settings')]
    public function settings(): Response
    {
        return $this->render('back-office/settings.html.twig');
    }

    #[Route('/admin/profile', name: 'admin_profile')]
    public function profile(): Response
    {
        return $this->render('back-office/profile.html.twig');
    }
    #[Route('/admin/contact', name: 'admin_contact')]
    public function contact(): Response
    {
        return $this->render('back-office/contact.html.twig');
    }
#[Route('/admin/response', name: 'admin_response')]
    public function response(): Response
    {
        return $this->render('back-office/response.html.twig');
    }




    
}