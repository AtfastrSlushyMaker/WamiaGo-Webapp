<?php

namespace App\Service;

use App\Entity\Trip;
use App\Repository\TripRepository;
use Doctrine\ORM\EntityManagerInterface;

class TripService
{
    private $entityManager;
    private $tripRepository;

    public function __construct(EntityManagerInterface $entityManager, TripRepository $tripRepository)
    {
        $this->entityManager = $entityManager;
        $this->tripRepository = $tripRepository;
    }

    public function getTrip(int $id): ?Trip
    {
        return $this->tripRepository->find($id);
    }

    public function createTrip(array $data): Trip
    {
        $trip = new Trip();
        $trip->setStartLocation($data['startLocation']);
        $trip->setEndLocation($data['endLocation']);
        $trip->setDistance($data['distance']);
        $trip->setStartTime(new \DateTime($data['startTime']));
        $trip->setEndTime(new \DateTime($data['endTime']));

        $this->entityManager->persist($trip);
        $this->entityManager->flush();

        return $trip;
    }

    public function updateTrip(Trip $trip, array $data): Trip
    {
        if (isset($data['startLocation'])) {
            $trip->setStartLocation($data['startLocation']);
        }
        if (isset($data['endLocation'])) {
            $trip->setEndLocation($data['endLocation']);
        }
        if (isset($data['distance'])) {
            $trip->setDistance($data['distance']);
        }
        if (isset($data['startTime'])) {
            $trip->setStartTime(new \DateTime($data['startTime']));
        }
        if (isset($data['endTime'])) {
            $trip->setEndTime(new \DateTime($data['endTime']));
        }

        $this->entityManager->flush();

        return $trip;
    }

    public function deleteTrip(Trip $trip): void
    {
        $this->entityManager->remove($trip);
        $this->entityManager->flush();
    }

    public function getAllTrips(): array
    {
        return $this->tripRepository->findAll();
    }
}