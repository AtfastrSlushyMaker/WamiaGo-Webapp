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
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly BicycleService $bicycleService,
        private readonly BicycleStationService $stationService,
        private readonly BicycleRentalService $rentalService,
        private readonly Security $security,
        private readonly ValidatorInterface $validator
    ) {
    }

    /**
     * Main bicycle rentals page
     */
    #[Route('/', name: 'app_front_services_bicycle')]
    public function index(): Response
    {
        // Refresh available bikes counts to ensure accuracy
        $this->stationService->refreshAvailableBikesCounts();

        // Get active stations for the map and listings
        $stations = $this->stationService->getAllActiveStations();

        // Get user's active rentals
        $activeRentals = [];
        $user = $this->entityManager->getRepository(User::class)->find(1);
        if ($user) {
            $activeRentals = $this->rentalService->getActiveRentalsForUser($user);
        }

        return $this->render('front/bicycle/bicycle-rental.html.twig', [
            'stations' => $stations,
            'activeRentals' => $activeRentals
        ]);
    }

    /**
     * View station details with available bicycles
     */
    #[Route('/station/{id}', name: 'app_front_services_bicycle_station', requirements: ['id' => '\d+'])]
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

    /**
     * View bicycle details
     */
    #[Route('/bicycle/{id}', name: 'app_front_services_bicycle_details', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function bicycleDetails(int $id): Response
    {
        $bicycle = $this->bicycleService->getBicycle($id);

        if (!$bicycle) {
            throw $this->createNotFoundException('Bicycle not found');
        }

        $bicycleInfo = $this->getBicycleDisplayInfo($bicycle);

        return $this->render('front/bicycle/bicycle-details.html.twig', [
            'bicycle' => $bicycle,
            'bicycleType' => $bicycleInfo['type'],
            'hourlyRate' => $bicycleInfo['hourlyRate']
        ]);
    }

    /**
     * Reserve a bicycle
     */
    #[Route('/bicycle/{id}/reserve', name: 'app_front_reserve_bicycle', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
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

        $bicycleInfo = $this->getBicycleDisplayInfo($bicycle);

        // Process form submission
        if ($request->isMethod('POST')) {
            $duration = (int) $request->request->get('duration');
            $estimatedCost = (float) $request->request->get('estimatedCost');

            // Validate input
            $validationResult = $this->validateReservationInput([
                'duration' => $duration,
                'estimatedCost' => $estimatedCost
            ]);

            if (!$validationResult['isValid']) {
                foreach ($validationResult['errors'] as $error) {
                    $this->addFlash('error', $error);
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
            'bicycleType' => $bicycleInfo['type'],
            'hourlyRate' => $bicycleInfo['hourlyRate']
        ]);
    }

    /**
     * Rental confirmation page
     */
    #[Route('/rental/{id}/confirmation', name: 'app_front_rental_confirmation', requirements: ['id' => '\d+'])]
    public function rentalConfirmation(int $id): Response
    {
        $rental = $this->entityManager->getRepository(BicycleRental::class)->find($id);
        $user = $this->entityManager->getRepository(User::class)->find(1);

        if (!$rental || $rental->getUser() !== $user) {
            throw $this->createNotFoundException('Rental not found');
        }

        return $this->render('front/bicycle/confirmation.html.twig', [
            'rental' => $rental,
            'reservationCode' => $this->generateReservationCode($rental)
        ]);
    }

    /**
     * Show rental code for pickup
     */
    #[Route('/rental/{id}/code', name: 'app_front_show_rental_code', requirements: ['id' => '\d+'])]
    public function showRentalCode(int $id): Response
    {
        $rental = $this->entityManager->getRepository(BicycleRental::class)->find($id);
        $user = $this->entityManager->getRepository(User::class)->find(1);

        if (!$rental || $rental->getUser() !== $user) {
            throw $this->createNotFoundException('Rental not found');
        }

        return $this->render('front/bicycle/rental-code.html.twig', [
            'rental' => $rental,
            'reservationCode' => $this->generateReservationCode($rental)
        ]);
    }

    /**
     * Cancel a rental
     */
    #[Route('/rental/{id}/cancel', name: 'app_front_cancel_rental', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
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

    /**
     * View user's reservations
     */
    #[Route('/my-reservations', name: 'app_front_my_reservations')]
    public function myReservations(): Response
    {
        $user = $this->entityManager->getRepository(User::class)->find(1);
        $activeRentals = $this->rentalService->getActiveRentalsForUser($user);
        $pastRentals = $this->rentalService->getPastRentalsForUser($user);

        return $this->render('front/bicycle/my-reservations.html.twig', [
            'activeRentals' => $activeRentals,
            'pastRentals' => $pastRentals
        ]);
    }

    /**
     * API: Get all active stations
     */
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
                'availableBikes' => $station->getAvailableBikes(),
                'availableDocks' => $station->getAvailableDocks(),
                'chargingBikes' => $station->getChargingBikes(),
                'totalDocks' => $station->getTotalDocks(),
                'status' => $station->getStatus()->value
            ];
        }

        return new JsonResponse($stationsData);
    }

    /**
     * API: Get bicycles at a station
     */
    #[Route('/station/{id}/bicycles', name: 'app_api_station_bicycles', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function getStationBicycles(int $id): JsonResponse
    {
        $station = $this->stationService->getStation($id);

        if (!$station) {
            return new JsonResponse(['error' => 'Station not found'], Response::HTTP_NOT_FOUND);
        }

        $bicycles = $this->bicycleService->getBicyclesByStation($station, true);
        $bicycleData = [];

        foreach ($bicycles as $bicycle) {
            $bicycleInfo = $this->getBicycleDisplayInfo($bicycle);
            $bicycleData[] = [
                'id' => $bicycle->getIdBike(),
                'batteryLevel' => $bicycle->getBatteryLevel(),
                'rangeKm' => $bicycle->getRangeKm(),
                'type' => $bicycleInfo['type'],
                'lastUpdated' => $bicycle->getLastUpdated()->format('Y-m-d H:i:s'),
                'status' => $bicycle->getStatus()->value,
                'hourlyRate' => $bicycleInfo['hourlyRate']
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

    /**
     * API: Get bicycle details
     */
    #[Route('/bicycle/{id}', name: 'app_api_bicycle_detail', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function getBicycleDetails(int $id): JsonResponse
    {
        $bicycle = $this->bicycleService->getBicycle($id);

        if (!$bicycle) {
            return new JsonResponse(['error' => 'Bicycle not found'], Response::HTTP_NOT_FOUND);
        }

        $bicycleInfo = $this->getBicycleDisplayInfo($bicycle);

        return new JsonResponse([
            'id' => $bicycle->getIdBike(),
            'type' => $bicycleInfo['type'],
            'batteryLevel' => $bicycle->getBatteryLevel(),
            'rangeKm' => $bicycle->getRangeKm(),
            'lastUpdated' => $bicycle->getLastUpdated()->format('Y-m-d H:i:s'),
            'station' => [
                'id' => $bicycle->getBicycleStation()->getIdStation(),
                'name' => $bicycle->getBicycleStation()->getName(),
                'location' => $bicycle->getBicycleStation()->getLocation() ?
                    $bicycle->getBicycleStation()->getLocation()->getAddress() : null
            ],
            'hourlyRate' => $bicycleInfo['hourlyRate']
        ]);
    }

    /**
     * API: Reserve a bicycle
     */
    #[Route('/reserve/{id}', name: 'app_api_reserve_bicycle', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function apiReserveBicycle(int $id, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        // Validate input data
        $validationResult = $this->validateReservationInput($data);
        if (!$validationResult['isValid']) {
            return new JsonResponse(['error' => $validationResult['errors']], Response::HTTP_BAD_REQUEST);
        }

        $bicycle = $this->bicycleService->getBicycle($id);

        if (!$bicycle) {
            return new JsonResponse(['error' => 'Bicycle not found'], Response::HTTP_NOT_FOUND);
        }

        if ($bicycle->getStatus() !== BICYCLE_STATUS::AVAILABLE) {
            return new JsonResponse(['error' => 'This bicycle is not available for reservation'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $user = $this->entityManager->getRepository(User::class)->find(1);
            $rental = $this->rentalService->reserveBicycle(
                $user,
                $bicycle,
                $data['estimatedCost']
            );

            return new JsonResponse([
                'success' => true,
                'rentalId' => $rental->getIdUserRental(),
                'reservationCode' => $this->generateReservationCode($rental),
                'message' => 'Bicycle reserved successfully!'
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * API: Cancel a rental
     */
    #[Route('/api/rental/{id}/cancel', name: 'app_api_cancel_rental', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function apiCancelRental(int $id): JsonResponse
    {
        $rental = $this->entityManager->getRepository(BicycleRental::class)->find($id);
        $user = $this->entityManager->getRepository(User::class)->find(1);

        if (!$rental) {
            return new JsonResponse(['error' => 'Rental not found'], Response::HTTP_NOT_FOUND);
        }

        if ($rental->getUser() !== $user) {
            return new JsonResponse(['error' => 'You can only cancel your own reservations'], Response::HTTP_FORBIDDEN);
        }

        try {
            $this->rentalService->cancelRental($rental);
            return new JsonResponse([
                'success' => true,
                'message' => 'Rental cancelled successfully'
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Helper function to get display information for a bicycle
     */
    private function getBicycleDisplayInfo(Bicycle $bicycle): array
    {
        $isPremium = $bicycle->getBatteryLevel() > 90;
        
        return [
            'type' => $isPremium ? 'Premium E-Bike' : 'Standard E-Bike',
            'hourlyRate' => $isPremium ? 5.00 : 3.50
        ];
    }

    /**
     * Helper function to generate a reservation code
     */
    private function generateReservationCode(BicycleRental $rental): string
    {
        return 'B' . str_pad($rental->getIdUserRental(), 5, '0', STR_PAD_LEFT);
    }

    /**
     * Helper function to validate reservation input
     */
    private function validateReservationInput(array $data): array
    {
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

        $errors = $this->validator->validate($data, $constraints);
        $errorMessages = [];
        
        if (count($errors) > 0) {
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            return ['isValid' => false, 'errors' => $errorMessages];
        }
        
        return ['isValid' => true, 'errors' => []];
    }
}