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
    // Define required keys
    $requiredKeys = [
        'departure_city',
        'arrival_city',
        'departure_date',
        'available_seats',
        'price_per_passenger',
        'id_driver',
        'id_vehicle'
    ];


    // Create and populate the Trip entity
    $trip = new Trip();
    $trip->setDeparture_city($data['departure_city']);
    $trip->setArrival_city($data['arrival_city']);
    $trip->setDeparture_date(new \DateTime($data['departure_date']));
    $trip->setAvailableSeats($data['available_seats']);
    $trip->setPricePerPassenger($data['price_per_passenger']);
    $trip->setDriver($data['id_driver']);
    $trip->setVehicle($data['id_vehicle']);

    // Persist and flush the entity
    $this->entityManager->persist($trip);
    $this->entityManager->flush();

    return $trip;
}

    public function updateTrip(Trip $trip, array $data): Trip
    {
        if (isset($data['departure_city'])) {
            $trip->setDepartureCity($data['departure_city']);
        }
        if (isset($data['arrival_city'])) {
            $trip->setArrivalCity($data['arrival_city']);
        }
        if (isset($data['departure_date'])) {
            $trip->setDepartureDate(new \DateTime($data['departure_date']));
        }
        if (isset($data['available_seats'])) {
            $trip->setAvailableSeats($data['available_seats']);
        }
        if (isset($data['price_per_passenger'])) {
            $trip->setPricePerPassenger($data['price_per_passenger']);
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