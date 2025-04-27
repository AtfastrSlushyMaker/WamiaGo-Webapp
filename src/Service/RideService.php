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

public function getTotalRidesCount(): int
{
    return $this->rideRepository->count([]);
}

public function getRidesPriceStatistics(): array
{
    $qb = $this->rideRepository->createQueryBuilder('r');
    $result = $qb->select('MIN(r.price) as min_price, MAX(r.price) as max_price, AVG(r.price) as avg_price')
        ->getQuery()
        ->getSingleResult();

    return [
        'min' => (float) $result['min_price'],
        'max' => (float) $result['max_price'],
        'avg' => (float) $result['avg_price']
    ];
}
public function calculateTotalRidesPrice(): float
{
    $qb = $this->rideRepository->createQueryBuilder('r');
    $result = $qb->select('SUM(r.price) as total_price')
        ->getQuery()
        ->getSingleResult();

    return (float) ($result['total_price'] ?? 0.0);
}

public function getAverageDurationTimeStat(): array
{
    $qb = $this->rideRepository->createQueryBuilder('r');
    $result = $qb->select('MIN(r.duration) as min_duration, MAX(r.duration) as max_duration, AVG(r.duration) as avg_duration')
        ->where('r.duration IS NOT NULL')
        ->getQuery()
        ->getSingleResult();

    return [
        'min' => (float) $result['min_duration'],
        'max' => (float) $result['max_duration'],
        'avg' => (float) $result['avg_duration']
    ];
}
public function getRidesCountPerWeek(): array
{
    $qb = $this->rideRepository->createQueryBuilder('r');
    return $qb->select('WEEK(r.rideDate) as week, COUNT(r.id) as count')
        ->groupBy('week')
        ->getQuery()
        ->getResult();
}

public function calculateAverageRideDuration(): float
{
    $rides = $this->rideRepository->findAll();
    if (empty($rides)) {
        return 0.0;
    }

    $totalDuration = 0;
    $validRides = 0;

    foreach ($rides as $ride) {
        $duration = $ride->getDuration();
        if ($duration !== null) {
            $totalDuration += $duration;
            $validRides++;
        }
    }

    return $validRides > 0 ? round($totalDuration / $validRides, 2) : 0.0;
}


public function calculateAverageRidesPrice(): float
{
    $rides = $this->rideRepository->findAll();
    if (empty($rides)) {
        return 0.0;
    }

    $totalPrice = 0;
    foreach ($rides as $ride) {
        $totalPrice += $ride->getPrice();
    }

    return $totalPrice / count($rides);
}

public function getRidesCountByStatus(): array
{
    $qb = $this->rideRepository->createQueryBuilder('r');
    $result = $qb->select('r.status, COUNT(r.id) as count')
        ->groupBy('r.status')
        ->getQuery()
        ->getResult();

    $counts = [
        RIDE_STATUS::COMPLETED->value => 0,
        RIDE_STATUS::CANCELED->value => 0,
        RIDE_STATUS::ONGOING->value => 0
    ];

    foreach ($result as $row) {
        $counts[$row['status']] = $row['count'];
    }

    return $counts;
}

public function getRidesStatusCount(): array
{
    return [
        'COMPLETED' => $this->rideRepository->count(['status' => RIDE_STATUS::COMPLETED]),
        'CANCELED' => $this->rideRepository->count(['status' => RIDE_STATUS::CANCELED]),
        'ONGOING' => $this->rideRepository->count(['status' => RIDE_STATUS::ONGOING])
    ];
}

}