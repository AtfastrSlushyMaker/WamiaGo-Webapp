<?php

namespace App\Service;

use App\Entity\Ride;
use App\Entity\Location;
use App\Entity\Driver;
use App\Entity\Request;
use App\Entity\User;
use App\Enum\RIDE_STATUS;
use App\Repository\RideRepository;
use Doctrine\ORM\EntityManagerInterface;
use PgSql\Lob;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Psr\Log\LoggerInterface;
use Doctrine\ORM\Query;

class RideService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private RideRepository $rideRepository,
        private LoggerInterface $logger
       
        
    ) {}

    public function createRide(Request $request, Driver $driver, ?int $duration = null): Ride
    {
        // Validate the request's locations
        $departureLocation = $request->getDepartureLocation();
        $arrivalLocation = $request->getArrivalLocation();

        if (!$departureLocation || !$arrivalLocation) {
            throw new \Exception('Invalid locations for the request.');
        }

        // Calculate the distance between the locations
        $distance = Location::calculateDistance($departureLocation, $arrivalLocation);

        // Calculate the price based on the distance
        $price = $this->calculatePrice($distance);

        // Validate the duration if provided
        if ($duration !== null && $duration > 90) {
            throw new \InvalidArgumentException('Duration cannot exceed 90 minutes.');
        }

        // Create a new Ride entity
        $ride = new Ride();
        $ride->setRequest($request) // Link the request
            ->setDriver($driver) // Link the driver
            ->setDistance($distance) // Set the distance
            ->setPrice($price) // Set the price
            ->setStatus(RIDE_STATUS::ONGOING) // Default status is ONGOING
            ->setRideDate(new \DateTime()); // Set the current timestamp
            
        // Set the duration if provided
        if ($duration !== null) {
            $ride->setDuration($duration);
        }

        // Persist the ride to the database
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
    public function getAllRides()
    {
        return $this->rideRepository->findAll();
    }
    private function calculatePrice(float $distance): float
    {
        $basePrice = 3.0; // Base price in TND
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

    public function updateRideDuration(int $rideId, int $duration): Ride
{
    // Validate the duration
    if ($duration <= 0 || $duration > 90) {
        throw new \Exception('Duration must be between 1 and 90 minutes.');
    }
    
    // Find the ride by ID
    $ride = $this->entityManager->getRepository(Ride::class)->find($rideId);
    
    if (!$ride) {
        throw new \Exception('Ride not found with ID: ' . $rideId);
    }
    
    // Update the duration
    $ride->setDuration($duration);
    
    // Persist the changes
    $this->entityManager->flush();
    
    return $ride;
}
public function getRidesByUser(int $userId): array
{
    $user = $this->entityManager->getRepository(User::class)->find($userId);

    if (!$user) {
        throw new \Exception('User not found with ID: ' . $userId);
    }

    // Query rides where the request's user matches the given user
    return $this->rideRepository->createQueryBuilder('r')
        ->join('r.request', 'req')
        ->where('req.user = :user')
        ->setParameter('user', $user)
        ->getQuery()
        ->getResult();
}


public function searchRides(array $criteria = []): array
{
    $qb = $this->rideRepository->createQueryBuilder('r');
    $qb->leftJoin('r.driver', 'd')
       ->leftJoin('r.request', 'req');

    foreach ($criteria as $field => $value) {
        if ($value === null || $value === '') {
            continue;
        }

        switch ($field) {
            case 'id':
                $qb->andWhere('r.id = :id')
                   ->setParameter('id', $value);
                break;
            
            case 'price':
                $qb->andWhere('r.price LIKE :price')
                   ->setParameter('price', '%' . $value . '%');
                break;

            case 'distance':
                $qb->andWhere('r.distance LIKE :distance')
                   ->setParameter('distance', '%' . $value . '%');
                break;

            case 'status':
                $qb->andWhere('r.status = :status')
                   ->setParameter('status', $value);
                break;

            case 'driver':
                if (is_string($value)) {
                    $qb->andWhere('d.name LIKE :driverName')
                       ->setParameter('driverName', '%' . $value . '%');
                } else {
                    $qb->andWhere('r.driver = :driver')
                       ->setParameter('driver', $value);
                }
                break;

            case 'request':
                if (is_numeric($value)) {
                    $qb->andWhere('req.id = :requestId')
                       ->setParameter('requestId', $value);
                } else {
                    $qb->andWhere('r.request = :request')
                       ->setParameter('request', $value);
                }
                break;
        }
    }

    return $qb->getQuery()->getResult();
}



}