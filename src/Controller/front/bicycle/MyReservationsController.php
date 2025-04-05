<?php

namespace App\Controller\front\bicycle;

use App\Entity\BicycleRental;
use App\Entity\BicycleStation;
use App\Entity\User;
use App\Service\BicycleRentalService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/my-reservations')]
class MyReservationsController extends AbstractController
{
    private $entityManager;
    private $rentalService;

    public function __construct(
        EntityManagerInterface $entityManager,
        BicycleRentalService $rentalService
    ) {
        $this->entityManager = $entityManager;
        $this->rentalService = $rentalService;
    }

    #[Route('/', name: 'app_front_my_reservations')]
    public function index(): Response
    {
        // Use static user with ID 1 until authentication is implemented
        $user = $this->entityManager->getRepository(User::class)->find(1);

        if (!$user) {
            throw $this->createNotFoundException('User not found');
        }

        $activeRentals = $this->rentalService->getActiveRentalsForUser($user);
        $activeRides = $this->rentalService->getActiveRidesForUser($user);
        $pastRentals = $this->rentalService->getPastRentalsForUser($user);

        // Get all available stations for returning bikes
        $stations = $this->entityManager->getRepository(BicycleStation::class)->findAll();

        return $this->render('front/bicycle/my-reservations.html.twig', [
            'activeRentals' => $activeRentals,
            'activeRides' => $activeRides,
            'pastRentals' => $pastRentals,
            'stations' => $stations,
            'user' => $user
        ]);
    }

    #[Route('/cancel/{id}', name: 'app_rental_cancel', methods: ['POST'])]
    public function cancelRental(int $id, Request $request): Response
    {
        // Your existing cancel rental code...
        return $this->redirectToRoute('app_front_my_reservations');
    }

    #[Route('/return-bike/{id}', name: 'app_rental_return_bike', methods: ['POST'])]
    public function returnBike(int $id, Request $request): Response
    {
        $rental = $this->entityManager->getRepository(BicycleRental::class)->find($id);

        if (!$rental) {
            $this->addFlash('error', 'Rental not found');
            return $this->redirectToRoute('app_front_my_reservations');
        }

        if ($rental->getEndTime() !== null) {
            $this->addFlash('error', 'This rental has already been completed');
            return $this->redirectToRoute('app_front_my_reservations');
        }

        if ($rental->getStartTime() === null) {
            $this->addFlash('error', 'You cannot return a bicycle that has not been unlocked yet');
            return $this->redirectToRoute('app_front_my_reservations');
        }

        // Get the station ID where the bicycle is being returned
        $stationId = $request->request->get('station_id');
        $station = $this->entityManager->getRepository(BicycleStation::class)->find($stationId);

        if (!$station) {
            $this->addFlash('error', 'Invalid return station selected');
            return $this->redirectToRoute('app_front_my_reservations');
        }

        // Calculate rental cost based on duration
        $now = new \DateTime();
        $startTime = $rental->getStartTime();
        $duration = $now->getTimestamp() - $startTime->getTimestamp();
        $hours = max(1, ceil($duration / 3600));

        // Determine rate based on bicycle type (premium or standard)
        $isPremium = $rental->getBicycle()->getBatteryLevel() > 90;
        $hourlyRate = $isPremium ? 5.0 : 3.5; // TND per hour

        $finalCost = $hours * $hourlyRate;

        // Update the rental record
        $rental->setEndTime($now);
        $rental->setEndStation($station);
        $rental->setCost($finalCost);

        // Update the bicycle to be available again and at the new station
        $bicycle = $rental->getBicycle();
        $bicycle->setBicycleStation($station);

        $this->entityManager->flush();

        $this->addFlash('success', 'Bicycle returned successfully! Your final cost is ' . number_format($finalCost, 3) . ' TND');
        return $this->redirectToRoute('app_front_my_reservations');
    }
}
