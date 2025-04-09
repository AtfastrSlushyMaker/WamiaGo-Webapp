<?php

namespace App\Service;

use App\Entity\Request;
use App\Entity\User;
use App\Entity\Location;
use App\Enum\REQUEST_STATUS;
use App\Repository\RequestRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class RequestService
{
    private $entityManager;
    private $requestRepository;

    public function __construct(EntityManagerInterface $entityManager, RequestRepository $requestRepository)
    {
        $this->entityManager = $entityManager;
        $this->requestRepository = $requestRepository;
    }

    public function createRequest(int $userId, string $pickupAddress, float $pickupLat, float $pickupLng,
                                  string $arrivalAddress, float $arrivalLat, float $arrivalLng,
                                  \DateTimeInterface $requestDate, string $status): Request
    {
        // Créer ou récupérer la location de départ (pickup)
        $departureLocation = $this->getOrCreateLocation($pickupAddress, $pickupLat, $pickupLng);

        // Créer ou récupérer la location d'arrivée (arrival)
        $arrivalLocation = $this->getOrCreateLocation($arrivalAddress, $arrivalLat, $arrivalLng);

        // Récupérer l'utilisateur à partir de son ID
        $user = $this->entityManager->getRepository(User::class)->find($userId);

        if (!$user) {
            throw new \Exception("User not found");
        }

        // Créer la nouvelle entité Request
        $request = new Request();
        $request->setUser($user)  // Optionnel : ici, vous pouvez directement assigner l'utilisateur si nécessaire
        ->setDepartureLocation($departureLocation)
            ->setArrivalLocation($arrivalLocation)
            ->setRequest_date($requestDate)
            ->setStatus(REQUEST_STATUS::from($status));

        // Persister la demande dans la base de données
        $this->entityManager->persist($request);
        $this->entityManager->flush();

        return $request;
    }

    // Méthode pour créer une location si elle n'existe pas encore
    private function getOrCreateLocation(string $address, float $latitude, float $longitude): Location
    {
        // Vérifier si la location existe déjà dans la base de données
        $existingLocation = $this->entityManager->getRepository(Location::class)->findOneBy([
            'address' => $address,
            'latitude' => $latitude,
            'longitude' => $longitude
        ]);

        // Si la location n'existe pas, créer une nouvelle
        if (!$existingLocation) {
            $existingLocation = new Location();
            $existingLocation->setAddress($address);
            $existingLocation->setLatitude($latitude);
            $existingLocation->setLongitude($longitude);

            // Persister la nouvelle location
            $this->entityManager->persist($existingLocation);
            $this->entityManager->flush();
        }

        return $existingLocation;
    }
    // Read a request by ID
    public function getRequestById(int $id): ?Request
    {
        return $this->requestRepository->find($id);
    }

    // Update an existing request
    public function updateRequest(int $id, array $data): ?Request
    {
        $request = $this->requestRepository->find($id);

        if (!$request) {
            throw new NotFoundHttpException('Request not found.');
        }

        // Update the request properties based on the provided data
        if (isset($data['departureLocation'])) {
            $request->setDepartureLocation($data['departureLocation']);
        }

        if (isset($data['arrivalLocation'])) {
            $request->setArrivalLocation($data['arrivalLocation']);
        }

        if (isset($data['request_date'])) {
            $request->setRequest_date($data['request_date']);
        }

        if (isset($data['status'])) {
            $request->setStatus($data['status']);
        }

        // Persist changes to the database
        $this->entityManager->flush();

        return $request;
    }

    // Delete a request by ID
    public function deleteRequest(int $id): bool
    {
        $request = $this->requestRepository->find($id);

        if (!$request) {
            throw new NotFoundHttpException('Request not found.');
        }

        $this->entityManager->remove($request);
        $this->entityManager->flush();

        return true;
    }

    // You can also add additional methods like listing requests for a user, etc.
    public function getRequestsForUser(int $userId): array
    {
        return $this->requestRepository->findBy(['user' => $userId]);
    }
}
