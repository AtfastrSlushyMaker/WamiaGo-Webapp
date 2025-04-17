<?php

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\BicycleRentalService;
use App\Service\BicycleStationService;
use App\Service\BicycleService;
use App\Form\BicycleType;
use App\Form\BicycleStationType;
use Symfony\Component\Form\FormFactoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;

class AdminController extends AbstractController
{
    private $bicycleRentalService;
    private $bicycleStationService;
    private $bicycleService;
    private $formFactory;
    private $entityManager;

    public function __construct(
        BicycleRentalService $bicycleRentalService,
        BicycleStationService $bicycleStationService,
        BicycleService $bicycleService,
        FormFactoryInterface $formFactory,
        EntityManagerInterface $entityManager
    ) {
        $this->bicycleRentalService = $bicycleRentalService;
        $this->bicycleStationService = $bicycleStationService;
        $this->bicycleService = $bicycleService;
        $this->formFactory = $formFactory;
        $this->entityManager = $entityManager;
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
    public function users(): Response
    {
        return $this->render('back-office/users.html.twig');
    }

    #[Route('/admin/ride-sharing', name: 'admin_ride_sharing')]
    public function rideSharing(): Response
    {
        return $this->render('back-office/ride-sharing.html.twig');
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
        $bicycles = $this->bicycleService->getAllBicycles();
        $stations = $this->bicycleStationService->getAllStations();
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

        // Rentals with filters and pagination
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
        $query = $queryBuilder->getQuery();
        $rentals = $query->getResult();

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

        // Station stats
        $stationCounts = $this->bicycleStationService->getStationCountsByStatus();
        $totalCapacity = $this->bicycleStationService->getTotalBicycleCapacity();
        $totalChargingDocks = $this->bicycleStationService->getTotalChargingDocks();
        $stationActivity = $this->bicycleStationService->getStationsWithRentalActivity(5);

        return $this->render('back-office/bicycle-rentals.html.twig', [
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
                'totalRentals' => count($rentals),
                'completedCount' => $completedCount,
                'activeCount' => $activeCount,
                'reservedCount' => $reservedCount,
                'completionRate' => count($rentals) > 0 ? round(($completedCount / count($rentals)) * 100) : 0,
                'totalRevenue' => array_sum(array_map(function($r) { return $r->getCost() ?: 0; }, $rentals))
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
            'active_tab' => 'rentals',
            'bicycleService' => $this->bicycleService,
            'stationService' => $this->bicycleStationService,
            'rentalService' => $this->bicycleRentalService,
        ]);
    }

    #[Route('/admin/relocations', name: 'admin_relocations')]
    public function relocations(): Response
    {
        return $this->render('back-office/relocations.html.twig');
    }

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
}
