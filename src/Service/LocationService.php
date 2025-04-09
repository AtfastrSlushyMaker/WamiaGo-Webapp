<?php

namespace App\Service;

use App\Entity\Location;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\LocationRepository;

class LocationService
{

    private $locationRepository;

    public function __construct(EntityManagerInterface $entityManager, LocationRepository $locationRepository)
    {
        $this->entityManager = $entityManager;
        $this->LocationRepository = $locationRepository;
    }

    // Méthode pour récupérer toutes les locations
    public function getAllLocations(): array
    {
        return $this->LocationRepository->findAll();  // Récupère toutes les entrées de location
    }

    public function getAllLocationAddresses(): array
    {
        $locations = $this->LocationRepository->findAll();  // Récupère toutes les locations
        $addresses = [];

        // Récupère l'adresse et les coordonnées pour chaque location
        foreach ($locations as $location) {
            $addresses[] = [
                'id_location' => $location->getId_location(),
                'address' => $location->getAddress(),
                'latitude' => $location->getLatitude(),
                'longitude' => $location->getLongitude(),
            ];
        }

        return $addresses;
    }


    // Méthode pour récupérer une location par son ID
    public function getLocationById(int $id): ?Location
    {
        return $this->LocationRepository->find($id);  // Recherche une location par son ID
    }

    public function createLocation(string $address, float $latitude, float $longitude): Location
    {
        // Créer une nouvelle instance de Location
        $location = new Location();
        $location->setAddress($address);
        $location->setLatitude($latitude);
        $location->setLongitude($longitude);

        // Persister la nouvelle location dans la base de données
        $this->entityManager->persist($location);
        $this->entityManager->flush();

        return $location;  // Retourner l'objet Location créé
    }
}
