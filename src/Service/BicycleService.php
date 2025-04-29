<?php

namespace App\Service;

use App\Entity\Bicycle;
use App\Entity\BicycleStation;
use App\Enum\BICYCLE_STATUS;
use App\Repository\BicycleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use App\Service\BicycleStationService;

class BicycleService
{
    private $entityManager;
    private $bicycleRepository;
    private $stationRepository;

    public function __construct(EntityManagerInterface $entityManager, BicycleRepository $bicycleRepository, BicycleStationService $stationService)
    {
        $this->entityManager = $entityManager;
        $this->bicycleRepository = $bicycleRepository;
        $this->stationRepository = $stationService;
    }
    public function getBicycleRepository(): EntityRepository
    {
        return $this->bicycleRepository;
    }



    public function getBicycle(int $id): ?Bicycle
    {
        return $this->bicycleRepository->find($id);
    }


    public function createBicycle(
        BicycleStation $station,
        float $batteryLevel,
        float $rangeKm,
        BICYCLE_STATUS $status = BICYCLE_STATUS::AVAILABLE
    ): Bicycle {
        $bicycle = new Bicycle();
        $bicycle->setBicycleStation($station);
        $bicycle->setBatteryLevel($batteryLevel);
        $bicycle->setRangeKm($rangeKm);
        
        // Ensure we're using a valid status
        $bicycle->setStatus($status);
        
        // If status is AVAILABLE but station is null, throw an exception
        if ($status === BICYCLE_STATUS::AVAILABLE && $station === null) {
            throw new \Exception('Cannot create an AVAILABLE bicycle without a station');
        }
        
        $bicycle->setLastUpdated(new \DateTime());

        $this->entityManager->persist($bicycle);
        $this->entityManager->flush();

        return $bicycle;
    }

    /**
     * Get all bicycles as a query for pagination
     */
    public function getAllBicyclesQuery()
    {
        return $this->bicycleRepository->createQueryBuilder('b')
            ->orderBy('b.idBike', 'DESC')
            ->getQuery();
    }
    
    /**
     * Get all bicycles
     */
    public function getAllBicycles(): array
    {
        return $this->bicycleRepository->findAll();
    }

    public function updateBicycle(Bicycle $bicycle): Bicycle
    {
        $bicycle->setLastUpdated(new \DateTime());
        $this->entityManager->flush();
        return $bicycle;
    }

    public function deleteBicycle(Bicycle $bicycle): void
    {
        $this->entityManager->remove($bicycle);
        $this->entityManager->flush();
    }

    public function changeBicycleStatus(Bicycle $bicycle, BICYCLE_STATUS $status): void
    {
        $previousStatus = $bicycle->getStatus();
        
        $bicycle->setStatus($status);
        $bicycle->setLastUpdated(new \DateTime());
        
        if ($status === BICYCLE_STATUS::AVAILABLE && $bicycle->getBicycleStation() === null) {
            throw new \Exception('Cannot set bicycle to AVAILABLE without a station');
        }
        
        $this->entityManager->flush();
    }

    public function updateBicycleStatus(int $bicycleId, BICYCLE_STATUS $status): void
    {
        $bicycle = $this->getBicycle($bicycleId);

        if (!$bicycle) {
            throw new \Exception('Bicycle not found');
        }

        $previousStatus = $bicycle->getStatus();
        
        $bicycle->setStatus($status);
        $bicycle->setLastUpdated(new \DateTime());
        
        if ($status === BICYCLE_STATUS::AVAILABLE && $bicycle->getBicycleStation() === null) {
            throw new \Exception('Cannot set bicycle to AVAILABLE without a station');
        }
        
        $this->entityManager->flush();
    }

    public function getBicyclesByStation(BicycleStation $station, bool $onlyAvailable = false): array
    {
        $criteria = ['bicycleStation' => $station];

        if ($onlyAvailable) {
            $criteria['status'] = BICYCLE_STATUS::AVAILABLE;
        }

        return $this->bicycleRepository->findBy($criteria);
    }
    public function getBicyclesByStationIds(array $ids): array
    {
        return $this->bicycleRepository->findBy(['bicycleStation' => $ids]);
    }
    public function getBicyclesByStatus(BICYCLE_STATUS $status): array
    {
        return $this->bicycleRepository->findBy(['status' => $status]);
    }
    public function getStation(int $id): ?BicycleStation
    {
        return $this->entityManager->find(BicycleStation::class, $id);
    }


    public function reassignBicycleToStation(Bicycle $bicycle, ?BicycleStation $station, ?BICYCLE_STATUS $newStatus = null): Bicycle
    {
        $oldStation = $bicycle->getBicycleStation();
        $currentStatus = $bicycle->getStatus();
        
        if ($newStatus === null) {
            if ($station === null) {

                $newStatus = BICYCLE_STATUS::MAINTENANCE;
            } else if ($currentStatus === BICYCLE_STATUS::MAINTENANCE || $currentStatus === BICYCLE_STATUS::CHARGING) {

                $newStatus = $currentStatus;
            } else {
                $newStatus = BICYCLE_STATUS::AVAILABLE;
            }
        }
        

        if ($station === null && $newStatus === BICYCLE_STATUS::AVAILABLE) {
            throw new \Exception('Cannot set a bicycle to AVAILABLE without assigning it to a station');
        }
        

        if ($oldStation !== null) {

            if ($currentStatus === BICYCLE_STATUS::AVAILABLE) {
                $oldStation->setAvailableBikes($oldStation->getAvailableBikes() - 1);
                $oldStation->setAvailableDocks($oldStation->getAvailableDocks() + 1);
            }
        }
        

        if ($station !== null && $newStatus === BICYCLE_STATUS::AVAILABLE) {
            $station->setAvailableBikes($station->getAvailableBikes() + 1);
            $station->setAvailableDocks($station->getAvailableDocks() - 1);
        }
        

        $bicycle->setBicycleStation($station);
        
        try {

            $bicycle->setStatus($newStatus);
        } catch (\LogicException $e) {

            $bicycle->setStatus(BICYCLE_STATUS::MAINTENANCE);
        }
        
        $bicycle->setLastUpdated(new \DateTime());
        
        $this->entityManager->flush();
        
        return $bicycle;
    }
}
