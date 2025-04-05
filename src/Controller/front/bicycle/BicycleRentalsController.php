<?php

namespace App\Controller\front\bicycle;

use App\Entity\Bicycle;
use App\Entity\BicycleStation;
use App\Entity\BicycleRental;
use App\Entity\User;
use App\Enum\BICYCLE_STATUS;
use App\Service\BicycleService;
use App\Service\BicycleStationService;
use App\Service\BicycleRentalService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Constraints as Assert;
use App\Repository\BicycleStationRepository;

#[Route('/services/bicycle')]
class BicycleRentalsController extends AbstractController
{
    private $entityManager;
    private $bicycleService;
    private $stationService;
    private $rentalService;
    private $security;
    private $validator;

    public function __construct(
        EntityManagerInterface $entityManager,
        BicycleService $bicycleService,
        BicycleStationService $stationService,
        BicycleRentalService $rentalService,
        Security $security,
        ValidatorInterface $validator
    ) {
        $this->entityManager = $entityManager;
        $this->bicycleService = $bicycleService;
        $this->stationService = $stationService;
        $this->rentalService = $rentalService;
        $this->security = $security;
        $this->validator = $validator;
    }

    #[Route('/', name: 'app_front_services_bicycle')]
    public function index(): Response
    {
        // Refresh available bikes counts to ensure accuracy
        $this->stationService->refreshAvailableBikesCounts();

        // Get active stations for the map and listings
        $stations = $this->stationService->getAllActiveStations();

        // Get user's active rentals if logged in
        $activeRentals = [];
        // Use a static user with ID 1 for all users
        $user = $this->entityManager->getRepository(User::class)->find(1);
        if ($user) {
            $activeRentals = $this->rentalService->getActiveRentalsForUser($user);
        }

        return $this->render('front/bicycle/bicycle-rental.html.twig', [
            'stations' => $stations,
            'activeRentals' => $activeRentals
        ]);
    }

    #[Route('/station/{id}', name: 'app_front_services_bicycle_station')]
    public function stationDetails(int $id): Response
    {
        $station = $this->stationService->getStation($id);

        if (!$station) {
            throw $this->createNotFoundException('Station not found');
        }

        // Get available bicycles at this station
        $bicycles = $this->bicycleService->getBicyclesByStation($station, true);

        return $this->render('front/bicycle/station.html.twig', [
            'station' => $station,
            'bicycles' => $bicycles
        ]);
    }

    #[Route('/bicycle/{id}', name: 'app_front_services_bicycle_details')]
    public function bicycleDetails(int $id): Response
    {
        $bicycle = $this->bicycleService->getBicycle($id);

        if (!$bicycle) {
            throw $this->createNotFoundException('Bicycle not found');
        }

        $isPremium = $bicycle->getBatteryLevel() > 90;
        $hourlyRate = $isPremium ? 5.00 : 3.50;

        return $this->render('front/bicycle/bicycle-details.html.twig', [
            'bicycle' => $bicycle,
            'bicycleType' => $isPremium ? 'Premium E-Bike' : 'Standard E-Bike',
            'hourlyRate' => $hourlyRate
        ]);
    }

    #[Route('/bicycle/{id}/reserve', name: 'app_front_reserve_bicycle', methods: ['GET', 'POST'])]
    public function reserveBicycle(Request $request, int $id): Response
    {
        $bicycle = $this->bicycleService->getBicycle($id);

        if (!$bicycle) {
            throw $this->createNotFoundException('Bicycle not found');
        }

        if ($bicycle->getStatus() !== BICYCLE_STATUS::AVAILABLE) {
            $this->addFlash('error', 'This bicycle is not available for reservation');
            return $this->redirectToRoute('app_front_services_bicycle_station', ['id' => $bicycle->getBicycleStation()->getIdStation()]);
        }

        $isPremium = $bicycle->getBatteryLevel() > 90;
        $hourlyRate = $isPremium ? 5.00 : 3.50;

        // Process form submission
        if ($request->isMethod('POST')) {
            $duration = (int) $request->request->get('duration');
            $estimatedCost = (float) $request->request->get('estimatedCost');

            // Validate input
            $constraints = new Assert\Collection([
                'duration' => [
                    new Assert\NotBlank(),
                    new Assert\Type(['type' => 'numeric']),
                    new Assert\Range(['min' => 1, 'max' => 24])
                ],
                'estimatedCost' => [
                    new Assert\NotBlank(),
                    new Assert\Type(['type' => 'numeric']),
                    new Assert\Positive()
                ]
            ]);

            $errors = $this->validator->validate([
                'duration' => $duration,
                'estimatedCost' => $estimatedCost
            ], $constraints);

            if (count($errors) > 0) {
                foreach ($errors as $error) {
                    $this->addFlash('error', $error->getMessage());
                }
            } else {
                try {
                    // Create reservation
                    $rental = $this->rentalService->reserveBicycle(
                        $this->entityManager->getRepository(User::class)->find(1),
                        $bicycle,
                        $estimatedCost
                    );

                    return $this->redirectToRoute('app_front_rental_confirmation', [
                        'id' => $rental->getIdUserRental()
                    ]);
                } catch (\Exception $e) {
                    $this->addFlash('error', $e->getMessage());
                }
            }
        }

        return $this->render('front/bicycle/reserve.html.twig', [
            'bicycle' => $bicycle,
            'bicycleType' => $isPremium ? 'Premium E-Bike' : 'Standard E-Bike',
            'hourlyRate' => $hourlyRate
        ]);
    }

    #[Route('/rental/{id}/confirmation', name: 'app_front_rental_confirmation')]
    public function rentalConfirmation(int $id): Response
    {
        $rental = $this->entityManager->getRepository(BicycleRental::class)->find($id);
        $user = $this->entityManager->getRepository(User::class)->find(1);

        if (!$rental || $rental->getUser() !== $user) {
            throw $this->createNotFoundException('Rental not found');
        }

        return $this->render('front/bicycle/confirmation.html.twig', [
            'rental' => $rental,
            'reservationCode' => 'B' . str_pad($rental->getIdUserRental(), 5, '0', STR_PAD_LEFT)
        ]);
    }

    #[Route('/rental/{id}/code', name: 'app_front_show_rental_code')]
    public function showRentalCode(int $id): Response
    {
        $rental = $this->entityManager->getRepository(BicycleRental::class)->find($id);
        $user = $this->entityManager->getRepository(User::class)->find(1);

        if (!$rental || $rental->getUser() !== $user) {
            throw $this->createNotFoundException('Rental not found');
        }

        return $this->render('front/bicycle/rental-code.html.twig', [
            'rental' => $rental,
            'reservationCode' => 'B' . str_pad($rental->getIdUserRental(), 5, '0', STR_PAD_LEFT)
        ]);
    }

    #[Route('/rental/{id}/cancel', name: 'app_front_cancel_rental', methods: ['GET', 'POST'])]
    public function cancelRental(Request $request, int $id): Response
    {
        $rental = $this->entityManager->getRepository(BicycleRental::class)->find($id);
        $user = $this->entityManager->getRepository(User::class)->find(1);

        if (!$rental || $rental->getUser() !== $user) {
            throw $this->createNotFoundException('Rental not found');
        }

        if ($request->isMethod('POST')) {
            $this->rentalService->cancelRental($rental);
            $this->addFlash('success', 'Your reservation has been cancelled');
            return $this->redirectToRoute('app_front_my_reservations');
        }

        return $this->render('front/bicycle/cancel-rental.html.twig', [
            'rental' => $rental
        ]);
    }

    #[Route('/my-reservations', name: 'app_front_my_reservations')]
    public function myReservations(): Response
    {
        $activeRentals = $this->rentalService->getActiveRentalsForUser(
            $this->entityManager->getRepository(User::class)->find(1)
        );
        $pastRentals = $this->rentalService->getPastRentalsForUser(
            $this->entityManager->getRepository(User::class)->find(1)
        );

        return $this->render('front/bicycle/my-reservations.html.twig', [
            'activeRentals' => $activeRentals,
            'pastRentals' => $pastRentals
        ]);
    }

    // API endpoints for AJAX operations
    #[Route('/stations', name: 'app_api_bicycle_stations', methods: ['GET'])]
    public function getStations(): JsonResponse
    {
        $stations = $this->stationService->getAllActiveStations();
        $stationsData = [];

        foreach ($stations as $station) {
            $stationsData[] = [
                'id' => $station->getIdStation(),
                'name' => $station->getName(),
                'location' => $station->getLocation() ? [
                    'lat' => $station->getLocation()->getLatitude(),
                    'lng' => $station->getLocation()->getLongitude(),
                    'address' => $station->getLocation()->getAddress()
                ] : null,
                'availableBikes' => $station->getAvailableBikes(),  // Use the station's own count
                'availableDocks' => $station->getAvailableDocks(),
                'chargingBikes' => $station->getChargingBikes(),
                'totalDocks' => $station->getTotalDocks(),
                'status' => $station->getStatus()->value
            ];
        }


        return new JsonResponse($stationsData);
    }

    #[Route('/station/{id}/bicycles', name: 'app_api_station_bicycles', methods: ['GET'])]
    public function getStationBicycles(int $id): JsonResponse
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
                'lastUpdated' => $bicycle->getLastUpdated()->format('Y-m-d H:i:s'),
                'status' => $bicycle->getStatus()->value,
                'hourlyRate' => $isPremium ? 5.00 : 3.50
            ];
        }

        return new JsonResponse([
            'station' => [
                'id' => $station->getIdStation(),
                'name' => $station->getName()
            ],
            'bicycles' => $bicycleData
        ]);
    }

    #[Route('/bicycle/{id}', name: 'app_api_bicycle_detail', methods: ['GET'])]
    public function getBicycleDetails(int $id): JsonResponse
    {
        $bicycle = $this->bicycleService->getBicycle($id);

        if (!$bicycle) {
            return new JsonResponse(['error' => 'Bicycle not found'], 404);
        }

        $isPremium = $bicycle->getBatteryLevel() > 90;

        return new JsonResponse([
            'id' => $bicycle->getIdBike(),
            'type' => $isPremium ? 'Premium E-Bike' : 'Standard E-Bike',
            'batteryLevel' => $bicycle->getBatteryLevel(),
            'rangeKm' => $bicycle->getRangeKm(),
            'lastUpdated' => $bicycle->getLastUpdated()->format('Y-m-d H:i:s'),
            'station' => [
                'id' => $bicycle->getBicycleStation()->getIdStation(),
                'name' => $bicycle->getBicycleStation()->getName(),
                'location' => $bicycle->getBicycleStation()->getLocation() ?
                    $bicycle->getBicycleStation()->getLocation()->getAddress() : null
            ],
            'hourlyRate' => $isPremium ? 5.00 : 3.50
        ]);
    }

    #[Route('/reserve/{id}', name: 'app_api_reserve_bicycle', methods: ['POST'])]
    public function apiReserveBicycle(int $id, Request $request): JsonResponse
    {
        // Use Symfony Assert to validate input
        $constraints = new Assert\Collection([
            'duration' => [
                new Assert\NotBlank(),
                new Assert\Type(['type' => 'numeric']),
                new Assert\Range(['min' => 1, 'max' => 24])
            ],
            'estimatedCost' => [
                new Assert\NotBlank(),
                new Assert\Type(['type' => 'numeric']),
                new Assert\Positive()
            ]
        ]);

        $data = json_decode($request->getContent(), true);

        // Validate input data
        $errors = $this->validator->validate($data, $constraints);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            return new JsonResponse(['error' => $errorMessages], 400);
        }

        $bicycle = $this->bicycleService->getBicycle($id);

        if (!$bicycle) {
            return new JsonResponse(['error' => 'Bicycle not found'], 404);
        }

        if ($bicycle->getStatus() !== BICYCLE_STATUS::AVAILABLE) {
            return new JsonResponse(['error' => 'This bicycle is not available for reservation'], 400);
        }

        try {
            // Replace this line using security->getUser() with a static user ID 1
            // $user = $this->security->getUser();
            $rental = $this->rentalService->reserveBicycle(
                $this->entityManager->getRepository(User::class)->find(1),
                $bicycle,
                $data['estimatedCost']
            );

            return new JsonResponse([
                'success' => true,
                'rentalId' => $rental->getIdUserRental(),
                'reservationCode' => 'B' . str_pad($rental->getIdUserRental(), 5, '0', STR_PAD_LEFT),
                'message' => 'Bicycle reserved successfully!'
            ]);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 500);
        }
    }

    #[Route('/api/rental/{id}/cancel', name: 'app_api_cancel_rental', methods: ['POST'])]
    public function apiCancelRental(int $id): JsonResponse
    {
        $rental = $this->entityManager->getRepository(BicycleRental::class)->find($id);
        $user = $this->entityManager->getRepository(User::class)->find(1);

        if (!$rental) {
            return new JsonResponse(['error' => 'Rental not found'], 404);
        }

        if ($rental->getUser() !== $user) {
            return new JsonResponse(['error' => 'You can only cancel your own reservations'], 403);
        }

        $this->rentalService->cancelRental($rental);

        return new JsonResponse(['success' => true]);
    }
}