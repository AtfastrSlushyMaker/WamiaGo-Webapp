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
        $bicycle->setStatus($status);
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
        $bicycle->setStatus($status);
        $bicycle->setLastUpdated(new \DateTime());
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
}
