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
use App\Enum\BICYCLE_STATUS;

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

        // Get all active rentals from repository
        $allActiveRentals = $this->entityManager->getRepository(BicycleRental::class)->findActiveRentalsByUser($user);
        
        // Split into reservations (not started yet) and active rides (already started)
        $reservations = [];
        $activeRides = [];
        
        foreach ($allActiveRentals as $rental) {
            if ($rental->getStartTime() === null) {
                $reservations[] = $rental;
            } else {
                $activeRides[] = $rental;
            }
        }
        
        // Get past rentals (completed rides)
        $pastRentals = $this->entityManager->getRepository(BicycleRental::class)->findPastRentalsByUser($user->getId_user());

        // Get all available stations for returning bikes
        $stations = $this->entityManager->getRepository(BicycleStation::class)->findAll();

        return $this->render('front/bicycle/my-reservations.html.twig', [
            'activeRentals' => $allActiveRentals, // Keep this for backward compatibility
            'reservations' => $reservations,
            'activeRides' => $activeRides,
            'pastRentals' => $pastRentals,
            'stations' => $stations,
            'user' => $user
        ]);
    }

    #[Route('/cancel/{id}', name: 'app_rental_cancel', methods: ['POST'])]
    public function cancelRental(int $id, Request $request): Response
    {
        $rental = $this->entityManager->getRepository(BicycleRental::class)->find($id);
        
        if (!$rental) {
            $this->addFlash('error', 'Rental not found');
        } elseif ($rental->getStartTime() !== null) {
            $this->addFlash('error', 'Cannot cancel a rental that has already started');
        } else {
            // Update the bicycle to be available again
            $bicycle = $rental->getBicycle();
            $bicycle->setBicycleStation($rental->getStartStation());
            $bicycle->setStatus(BICYCLE_STATUS::AVAILABLE);
            
            // Remove the rental
            $this->entityManager->remove($rental);
            $this->entityManager->flush();
            
            $this->addFlash('success', 'Reservation cancelled successfully');
        }
        
        return $this->redirectToRoute('app_front_my_reservations');
    }

    #[Route('/return-bike/{id}', name: 'app_rental_return_bike', methods: ['POST'])]
    public function returnBike(int $id, Request $request): Response
    {
        $redirectResponse = $this->redirectToRoute('app_front_my_reservations');
        
        $rental = $this->entityManager->getRepository(BicycleRental::class)->find($id);

        if (!$rental) {
            $this->addFlash('error', 'Rental not found');
        } elseif ($rental->getEndTime() !== null) {
            $this->addFlash('error', 'This rental has already been completed');
        } elseif ($rental->getStartTime() === null) {
            $this->addFlash('error', 'You cannot return a bicycle that has not been unlocked yet');
        } else {
            // Get the station ID where the bicycle is being returned
            $stationId = $request->request->get('station_id');
            $station = $this->entityManager->getRepository(BicycleStation::class)->find($stationId);

            if (!$station) {
                $this->addFlash('error', 'Invalid return station selected');
            } else {
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
                $bicycle->setStatus(BICYCLE_STATUS::AVAILABLE);

                $this->entityManager->flush();

                $this->addFlash('success', 'Bicycle returned successfully! Your final cost is ' . number_format($finalCost, 3) . ' TND');
            }
        }

        return $redirectResponse;
    }

    #[Route('/activate/{id}', name: 'app_rental_activate', methods: ['GET'])]
    public function activateRental(int $id): Response
    {
        $rental = $this->entityManager->getRepository(BicycleRental::class)->find($id);
        // Update bicycle status to IN_USE
        $bicycle = $rental->getBicycle();
        
        // Now set to IN_USE
        $bicycle->setStatus(BICYCLE_STATUS::IN_USE);
        
        // Set the start time to now
        $rental->setStartTime(new \DateTime());
        
        $this->entityManager->flush();
        
        $this->addFlash('success', 'Bicycle activated successfully! Your rental timer has started.');
        
        // Add this line to redirect back to the reservations page
        return $this->redirectToRoute('app_front_my_reservations');
    }
}

