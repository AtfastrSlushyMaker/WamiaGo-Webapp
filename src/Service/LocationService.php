<?php

namespace App\Service;

use App\Entity\Location;
use Doctrine\ORM\EntityManagerInterface;

class LocationService
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * Create a new location
     * 
     * @param string $address
     * @param float $latitude
     * @param float $longitude
     * @return Location
     */
    public function createLocation(string $address, float $latitude = null, float $longitude = null): Location
    {
        $location = new Location();
        $location->setAddress($address);

        if ($latitude !== null) {
            $location->setLatitude($latitude);
        }

        if ($longitude !== null) {
            $location->setLongitude($longitude);
        }

        $this->entityManager->persist($location);
        $this->entityManager->flush();

        return $location;
    }
    public function saveLocation(Location $location): Location
    {
        $this->entityManager->persist($location);
        $this->entityManager->flush();
        return $location;
    }

    /**
     * Get all locations
     * 
     * @return array
     */
    public function getAllLocations(): array
    {
        return $this->entityManager->getRepository(Location::class)->findAll();
    }

    /**
     * Find location by ID
     * 
     * @param int $id
     * @return Location|null
     */
    public function findLocationById(int $id): ?Location
    {
        return $this->entityManager->getRepository(Location::class)->find($id);
    }

    /**
     * Update location
     * 
     * @param Location $location
     * @param array $data
     * @return Location
     */
    public function updateLocation(Location $location, array $data): Location
    {
        if (isset($data['address'])) {
            $location->setAddress($data['address']);
        }

        if (isset($data['latitude'])) {
            $location->setLatitude($data['latitude']);
        }

        if (isset($data['longitude'])) {
            $location->setLongitude($data['longitude']);
        }

        $this->entityManager->flush();

        return $location;
    }

    /**
     * Delete location
     * 
     * @param Location $location
     * @return bool
     */
    public function deleteLocation(Location $location): bool
    {
        try {
            $this->entityManager->remove($location);
            $this->entityManager->flush();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Find locations by name (partial match)
     * 
     * @param string $name
     * @return array
     */
    public function findLocationsByName(string $name): array
    {
        $repository = $this->entityManager->getRepository(Location::class);
        return $repository->createQueryBuilder('l')
            ->where('l.name LIKE :name')
            ->setParameter('name', '%' . $name . '%')
            ->getQuery()
            ->getResult();
    }
}
