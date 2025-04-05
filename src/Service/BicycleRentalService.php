<?php

namespace App\Service;

use App\Entity\Bicycle;
use App\Entity\BicycleRental;
use App\Entity\BicycleStation;
use App\Entity\User;
use App\Enum\BICYCLE_STATUS;
use App\Repository\BicycleRentalRepository;
use Doctrine\ORM\EntityManagerInterface;

class BicycleRentalService
{
    private $entityManager;
    private $rentalRepository;
    private $bicycleService;

    public function __construct(
        EntityManagerInterface $entityManager,
        BicycleRentalRepository $rentalRepository,
        BicycleService $bicycleService
    ) {
        $this->entityManager = $entityManager;
        $this->rentalRepository = $rentalRepository;
        $this->bicycleService = $bicycleService;
    }
    public function createBicycleRental(User $user, BicycleRental $rental): BicycleRental
    {
        $this->entityManager->persist($rental);
        $this->entityManager->flush();
        return $rental;
    }
    public function updateBicycleRental(BicycleRental $rental): BicycleRental
    {
        $this->entityManager->flush();
        return $rental;
    }
    public function deleteBicycleRental(BicycleRental $rental): void
    {
        $this->entityManager->remove($rental);
        $this->entityManager->flush();
    }

    public function getActiveRentalsForUser(User $user): array
    {
        return $this->rentalRepository->findActiveRentalsByUser($user);
    }

    /**
     * Get past rentals for a user
     */
    public function getPastRentalsForUser(User $user): array
    {
        return $this->rentalRepository->findPastRentalsByUser($user->getId_user());
    }

    /**
     * Reserve a bicycle for a user
     */
    public function reserveBicycle(User $user, Bicycle $bicycle, float $estimatedCost): BicycleRental
    {
        // Check if bicycle is available
        if ($bicycle->getStatus() !== BICYCLE_STATUS::AVAILABLE) {
            throw new \Exception('This bicycle is not available for reservation.');
        }

        // Check if user already has active rentals
        $activeRentals = $this->getActiveRentalsForUser($user);
        if (count($activeRentals) >= 2) {
            throw new \Exception('You cannot have more than 2 active reservations at once.');
        }

        $station = $bicycle->getBicycleStation();

        // Create new rental
        $rental = new BicycleRental();
        $rental->setUser($user);
        $rental->setBicycle($bicycle);
        $rental->setStartStation($station);
        $rental->setStartTime(new \DateTime());
        $rental->setDistanceKm(0);
        $rental->setBatteryUsed(0);
        $rental->setCost($estimatedCost);

        // Update bicycle status
        $bicycle->setStatus(BICYCLE_STATUS::RESERVED);

        // Update station stats
        $station->setAvailableBikes($station->getAvailableBikes() - 1);

        // Persist changes
        $this->entityManager->persist($rental);
        $this->entityManager->flush();

        return $rental;
    }

    /**
     * Cancel a rental
     */
    public function cancelRental(BicycleRental $rental): void
    {
        $bicycle = $rental->getBicycle();
        $station = $rental->getStartStation();

        // Update bicycle status
        $bicycle->setStatus(BICYCLE_STATUS::AVAILABLE);

        // Update station stats
        $station->setAvailableBikes($station->getAvailableBikes() + 1);

        // Remove rental
        $this->entityManager->remove($rental);
        $this->entityManager->flush();
    }

    /**
     * Complete a rental (return bicycle to station)
     */
    public function completeRental(BicycleRental $rental, BicycleStation $endStation, float $distanceKm, float $batteryUsed): void
    {
        $bicycle = $rental->getBicycle();

        // Calculate final cost based on duration
        $startTime = $rental->getStartTime();
        $endTime = new \DateTime();
        $duration = $endTime->diff($startTime);
        $hours = $duration->h + ($duration->days * 24);
        $finalCost = $hours > 0 ? $hours * 3.5 : 3.5;

        // Update rental
        $rental->setEndStation($endStation);
        $rental->setEndTime($endTime);
        $rental->setDistanceKm($distanceKm);
        $rental->setBatteryUsed($batteryUsed);
        $rental->setCost($finalCost);

        // Update bicycle
        $bicycle->setStatus(BICYCLE_STATUS::AVAILABLE);
        $bicycle->setBicycleStation($endStation);
        $bicycle->setBatteryLevel($bicycle->getBatteryLevel() - $batteryUsed);

        // Update station stats
        $endStation->setAvailableBikes($endStation->getAvailableBikes() + 1);

        $this->entityManager->flush();
    }
}
