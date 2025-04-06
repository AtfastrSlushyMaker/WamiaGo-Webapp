<?php

namespace App\Controller\Admin;

use App\Entity\Bicycle;
use App\Entity\BicycleStation;
use App\Entity\BicycleRental;
use App\Entity\Location;
use App\Enum\BICYCLE_STATION_STATUS;
use App\Form\BicycleStationType;
use App\Service\BicycleService;
use App\Service\BicycleStationService;
use App\Service\BicycleRentalService;
use App\Service\LocationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

//#[IsGranted('ROLE_ADMIN')]
#[Route('/admin/bicycle')]
class BicycleAdminController extends AbstractController
{
    private $entityManager;
    private $bicycleService;
    private $stationService;
    private $rentalService;
    private $locationService;

    public function __construct(
        EntityManagerInterface $entityManager,
        BicycleService $bicycleService,
        BicycleStationService $stationService,
        BicycleRentalService $rentalService,
        LocationService $locationService
    ) {
        $this->entityManager = $entityManager;
        $this->bicycleService = $bicycleService;
        $this->stationService = $stationService;
        $this->rentalService = $rentalService;
        $this->locationService = $locationService;
    }

    #[Route('', name: 'admin_bicycle_dashboard')]
    public function index(Request $request): Response
    {
        $tab = $request->query->get('tab', 'rentals');

        // Get all bicycles, stations, and rentals
        $bicycles = $this->bicycleService->getAllBicycles();
        $stations = $this->stationService->getAllStations();
        $rentals = $this->entityManager->getRepository(BicycleRental::class)->findAll();

        $templateVars = [
            'bicycles' => $bicycles,
            'stations' => $stations,
            'bicycle_rentals' => $rentals,
            'active_tab' => $tab
        ];

        // Add additional variables needed for specific tabs
        if ($tab === 'stations') {
            $templateVars['stationCounts'] = $this->stationService->getStationCountsByStatus();
            $templateVars['totalCapacity'] = $this->stationService->getTotalBicycleCapacity();
            $templateVars['totalChargingDocks'] = $this->stationService->getTotalChargingDocks();
            $templateVars['stationActivity'] = $this->stationService->getStationsWithRentalActivity(5);
        }

        return $this->render('back-office/bicycle-rentals.html.twig', $templateVars);
    }

    #[Route('/add', name: 'admin_bicycle_add', methods: ['POST'])]
    public function addBicycle(Request $request): Response
    {
        $stationId = $request->request->get('stationId');
        $batteryLevel = $request->request->get('batteryLevel');
        $rangeKm = $request->request->get('rangeKm');

        $station = $this->stationService->getStation((int)$stationId);

        if (!$station) {
            $this->addFlash('error', 'Station not found');
            return $this->redirectToRoute('admin_bicycle_dashboard');
        }

        $bicycle = $this->bicycleService->createBicycle(
            $station,
            (float)$batteryLevel,
            (float)$rangeKm
        );

        $this->addFlash('success', 'Bicycle added successfully');
        return $this->redirectToRoute('admin_bicycle_dashboard', ['_fragment' => 'bicycles']);
    }

    #[Route('/edit/{id}', name: 'admin_bicycle_edit', methods: ['POST'])]
    public function editBicycle(Request $request, int $id): Response
    {
        $bicycle = $this->bicycleService->getBicycle($id);

        if (!$bicycle) {
            $this->addFlash('error', 'Bicycle not found');
            return $this->redirectToRoute('admin_bicycle_dashboard');
        }

        // Update bicycle properties
        $stationId = $request->request->get('stationId');
        $station = $this->stationService->getStation((int)$stationId);

        if ($station) {
            $bicycle->setBicycleStation($station);
        }

        $bicycle->setBatteryLevel((float)$request->request->get('batteryLevel'));
        $bicycle->setRangeKm((float)$request->request->get('rangeKm'));

        // Update and save
        $this->bicycleService->updateBicycle($bicycle);

        $this->addFlash('success', 'Bicycle updated successfully');
        return $this->redirectToRoute('admin_bicycle_dashboard', ['_fragment' => 'bicycles']);
    }
    #[Route('/stations', name: 'admin_bicycle_stations')]
    public function bicycleStations(): Response
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
    #[Route('/station/new', name: 'admin_bicycle_station_new')]
    public function newStation(Request $request): Response
    {
        $station = new BicycleStation();
        $station->setStatus(BICYCLE_STATION_STATUS::ACTIVE);

        // Initialize default values to prevent null errors
        $station->setAvailableBikes(0);
        $station->setAvailableDocks(0);
        $station->setTotalDocks(0);
        $station->setChargingBikes(0);

        $form = $this->createForm(BicycleStationType::class, $station);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->stationService->createStation($station);
                $this->addFlash('success', 'Station created successfully.');
                return $this->redirectToRoute('admin_bicycle_dashboard', ['tab' => 'stations']);
            } catch (\Exception $e) {
                $this->addFlash('error', 'Failed to create station: ' . $e->getMessage());
            }
        }

        return $this->render('back-office/bicycle/station-form.html.twig', [
            'form' => $form->createView(),
            'station' => $station,
            'title' => 'Create New Station',
            'submitButtonText' => 'Create Station',
            'is_new' => true,
        ]);
    }

    #[Route('/station/{id}/edit', name: 'admin_bicycle_station_edit')]
    public function editStation(int $id, Request $request): Response
    {
        $station = $this->stationService->getStation($id);

        if (!$station) {
            $this->addFlash('error', 'Station not found.');
            return $this->redirectToRoute('admin_bicycle_stations');
        }

        $form = $this->createForm(BicycleStationType::class, $station);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->stationService->updateStation($station);
                $this->addFlash('success', 'Station updated successfully.');
                return $this->redirectToRoute('admin_bicycle_dashboard', ['tab' => 'stations']);
            } catch (\Exception $e) {
                $this->addFlash('error', 'Failed to update station: ' . $e->getMessage());
            }
        }

        return $this->render('back-office/bicycle/station-form.html.twig', [
            'form' => $form->createView(),
            'station' => $station,
            'title' => 'Edit Station',
            'submitButtonText' => 'Update Station',
            'is_new' => false,
        ]);
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
            $this->addFlash('error', 'Failed to update station status: ' . $e->getMessage());
        }

        return $this->redirectToRoute('admin_bicycle_stations', ['tab' => 'stations']);
    }


    #[Route('/api/bicycle-stations', name: 'api_bicycle_stations', methods: ['GET'])]
    public function getApiStations(): JsonResponse
    {
        $stations = $this->stationService->getAllStations();

        $data = [];
        foreach ($stations as $station) {
            // Make sure we have a location object
            if ($station->getLocation()) {
                $data[] = [
                    'id' => $station->getIdStation(), // Using consistent method name
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
    }

    private function processStationForm(Request $request, BicycleStation $station = null): BicycleStation
    {
        $isNew = $station === null;
        if ($isNew) {
            $station = new BicycleStation();
        }

        // Process form
        $form = $this->createForm(BicycleStationType::class, $station);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Get the location data from the hidden fields
            $latitude = $request->request->get('station_latitude');
            $longitude = $request->request->get('station_longitude');
            $address = $request->request->get('station_address');

            // Debug output
            error_log("Location data received: Lat=$latitude, Lng=$longitude, Address=$address");

            // Create a new location if we have coordinates from the map
            if ($latitude && $longitude) {
                // Create a new Location entity
                $location = new Location();
                $location->setLatitude($latitude);
                $location->setLongitude($longitude);
                $location->setAddress($address ?: 'Unknown address');

                // Save the location to the database
                $this->entityManager->persist($location);
                $this->entityManager->flush();

                // Set this new location on the station
                $station->setLocation($location);

                error_log("Created new location with ID: " . $location->getIdLocation());
            } else if ($form->get('location')->getData()) {
                // If no map selection but dropdown was used
                $station->setLocation($form->get('location')->getData());
                error_log("Using selected location from dropdown");
            }

            // If we still have no location, show an error
            if (!$station->getLocation()) {
                $this->addFlash('error', 'Please select a location for the station either from the dropdown or by clicking on the map.');
                return $station;
            }

            // Set other station properties
            if ($isNew) {
                $availableBikes = $request->request->get('availableBikes', 0);
                $station->setAvailableBikes((int)$availableBikes);

                if (!$station->getStatus()) {
                    $station->setStatus(BICYCLE_STATION_STATUS::ACTIVE);
                }
            } else {
                $availableBikes = $request->request->get('availableBikes', 0);
                $station->setAvailableBikes((int)$availableBikes);
            }

            // Save the station
            $this->entityManager->persist($station);
            $this->entityManager->flush();

            $this->addFlash('success', ($isNew ? 'Created' : 'Updated') . ' station successfully.');

            return $station;
        }

        return $station;
    }
}
