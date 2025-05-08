<?php

namespace App\Service;

use App\Entity\BicycleStation;
use App\Repository\BicycleStationRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Enum\BICYCLE_STATION_STATUS;
use phpDocumentor\Reflection\Types\Boolean;

class BicycleStationService
{
    private $entityManager;
    private $stationRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        BicycleStationRepository $stationRepository
    ) {
        $this->entityManager = $entityManager;
        $this->stationRepository = $stationRepository;
    }
    public function createStation(BicycleStation $bicycleStation): BicycleStation
    {
        $this->entityManager->persist($bicycleStation);
        $this->entityManager->flush();
        return $bicycleStation;
    }
    public function updateStation(BicycleStation $bicycleStation): BicycleStation
    {
        $this->entityManager->flush();
        return $bicycleStation;
    }
    public function deleteStation(BicycleStation $bicycleStation): void
    {
        $this->entityManager->remove($bicycleStation);
        $this->entityManager->flush();
    }
    public function getStation(int $id): ?BicycleStation
    {
        return $this->stationRepository->find($id);
    }
    public function getAllStations(): array
    {
        return $this->stationRepository->findAll();
    }

    public function getStationsByName(string $name): array
    {
        return $this->stationRepository->findBy(['name' => $name]);
    }
    public function getAllActiveStations(): array
    {
        return $this->stationRepository->findBy(['status' => BICYCLE_STATION_STATUS::ACTIVE]);
    }
    public function getAllInactiveStations(): array
    {
        return $this->stationRepository->findBy(['status' => BICYCLE_STATION_STATUS::INACTIVE]);
    }
    public function getStationsWithAvailableBikes(): array
    {
        return $this->stationRepository->getStationsWithAvailableBikes();
    }
    public function getAvailableBikes(): array
{
    // Return stations that have at least one bike available
    return $this->stationRepository->createQueryBuilder('s')
        ->where('s.available_bikes > 0')
        ->andWhere('s.status = :active')
        ->setParameter('active', BICYCLE_STATION_STATUS::ACTIVE)
        ->getQuery()
        ->getResult();
}

/**
 * Refresh the available bikes count for all stations
 */
public function refreshAvailableBikesCounts(): void
{
    $this->stationRepository->updateAvailableBikesCounts();
}
}
