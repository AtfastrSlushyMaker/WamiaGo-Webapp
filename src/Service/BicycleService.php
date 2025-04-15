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
        // Store previous status for logging
        $previousStatus = $bicycle->getStatus();
        
        // Update status with proper enum value
        $bicycle->setStatus($status);
        $bicycle->setLastUpdated(new \DateTime());
        
        // If changing to AVAILABLE status, ensure bicycle has a station
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

        // Store previous status for logging
        $previousStatus = $bicycle->getStatus();
        
        // Update status with proper enum value
        $bicycle->setStatus($status);
        $bicycle->setLastUpdated(new \DateTime());
        
        // If changing to AVAILABLE status, ensure bicycle has a station
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

    /**
     * Safely move a bicycle to a new station, handling status transitions appropriately
     * 
     * @param Bicycle $bicycle The bicycle to reassign
     * @param BicycleStation|null $station The destination station (or null to remove from any station)
     * @param BICYCLE_STATUS|null $newStatus Optional new status to set (if null, will automatically determine appropriate status)
     * @return Bicycle
     */
    public function reassignBicycleToStation(Bicycle $bicycle, ?BicycleStation $station, ?BICYCLE_STATUS $newStatus = null): Bicycle
    {
        $oldStation = $bicycle->getBicycleStation();
        $currentStatus = $bicycle->getStatus();
        
        // Determine the appropriate status based on the situation if not explicitly provided
        if ($newStatus === null) {
            if ($station === null) {
                // If removing from a station without specifying status, default to MAINTENANCE
                // This prevents bicycles without stations from being marked AVAILABLE
                $newStatus = BICYCLE_STATUS::MAINTENANCE;
            } else if ($currentStatus === BICYCLE_STATUS::MAINTENANCE || $currentStatus === BICYCLE_STATUS::CHARGING) {
                // Keep maintenance or charging status when moving to a new station
                $newStatus = $currentStatus;
            } else {
                // Default to AVAILABLE when adding to a station
                $newStatus = BICYCLE_STATUS::AVAILABLE;
            }
        }
        
        // Validate the status makes sense for the new station assignment
        if ($station === null && $newStatus === BICYCLE_STATUS::AVAILABLE) {
            throw new \Exception('Cannot set a bicycle to AVAILABLE without assigning it to a station');
        }
        
        // Update old station metrics if applicable
        if ($oldStation !== null) {
            // Decrement the old station's bike count if the bike was available there
            if ($currentStatus === BICYCLE_STATUS::AVAILABLE) {
                $oldStation->setAvailableBikes($oldStation->getAvailableBikes() - 1);
                $oldStation->setAvailableDocks($oldStation->getAvailableDocks() + 1);
            }
        }
        
        // Update new station metrics if applicable
        if ($station !== null && $newStatus === BICYCLE_STATUS::AVAILABLE) {
            // Increment the new station's bike count if the bike will be available there
            $station->setAvailableBikes($station->getAvailableBikes() + 1);
            $station->setAvailableDocks($station->getAvailableDocks() - 1);
        }
        
        // Update the bicycle
        $bicycle->setBicycleStation($station);
        
        try {
            // Status might throw an exception if invalid
            $bicycle->setStatus($newStatus);
        } catch (\LogicException $e) {
            // If we get a logic exception about AVAILABLE status without station,
            // switch to MAINTENANCE status as a safe default
            $bicycle->setStatus(BICYCLE_STATUS::MAINTENANCE);
        }
        
        $bicycle->setLastUpdated(new \DateTime());
        
        $this->entityManager->flush();
        
        return $bicycle;
    }
}
