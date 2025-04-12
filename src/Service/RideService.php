<?php

namespace App\Service;

use App\Entity\Ride;
use App\Entity\Location;
use App\Entity\Driver;
use App\Entity\Request;
use App\Enum\RIDE_STATUS;
use App\Repository\RideRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class RideService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private RideRepository $rideRepository,
        private LocationService $locationService
    ) {}

    public function createRide(Request $request, Driver $driver): Ride
    {
     
        $distance = Location::calculateDistance(
            $request->getDepartureLocation(),
            $request->getArrivalLocation()
        );
        $ride = new Ride();
        $ride->setRequest($request)
            ->setDriver($driver)
            ->setDistance($distance)
            ->setStatus(RIDE_STATUS::ONGOING)
            ->setRideDate(new \DateTime())
            ->setPrice($this->calculatePrice($distance));

        $this->entityManager->persist($ride);
        $this->entityManager->flush();

        return $ride;
    }

    public function getRide(int $id): ?Ride
    {
        $ride = $this->rideRepository->find($id);
        
        if (!$ride) {
            throw new NotFoundHttpException('Ride not found');
        }

        return $ride;
    }

    public function getRidesByDriver(Driver $driver): array
    {
        return $this->rideRepository->findBy(['driver' => $driver]);
    }

    public function getActiveRidesByDriver(Driver $driver): array
    {
        return $this->rideRepository->findBy([
            'driver' => $driver,
            'status' => RIDE_STATUS::ONGOING
        ]);
    }

    public function updateRideStatus(int $rideId, RIDE_STATUS $newStatus): Ride
    {
        $ride = $this->getRide($rideId);
        $ride->setStatus($newStatus);

        if ($newStatus === RIDE_STATUS::COMPLETED) {
            // Calculate duration when ride is completed
            $duration = time() - $ride->getRideDate()->getTimestamp();
            $ride->setDuration($duration);
        }

        $this->entityManager->flush();
        return $ride;
    }

    private function calculatePrice(float $distance): float
    {
        $basePrice = 5.0; // Base price in TND
        $pricePerKm = 1.5; // Price per kilometer
        
        return $basePrice + ($distance * $pricePerKm);
    }

    public function getRidesByRequest(Request $request): array
    {
        return $this->rideRepository->findBy(['request' => $request]);
    }

    public function deleteRide(int $id): void
    {
        $ride = $this->getRide($id);
        $this->entityManager->remove($ride);
        $this->entityManager->flush();
    }
}