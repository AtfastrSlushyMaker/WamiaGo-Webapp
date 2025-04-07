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

    // Create a new request
    public function createRequest(int $userId, int $departureLocationId, int $arrivalLocationId, \DateTimeInterface $requestDate, string $status): Request
    {
        // Retrieve the User and Location entities by their IDs
        $user = $this->entityManager->getRepository(User::class)->find($userId);
        $departureLocation = $this->entityManager->getRepository(Location::class)->find($departureLocationId);
        $arrivalLocation = $this->entityManager->getRepository(Location::class)->find($arrivalLocationId);

        if (!$user || !$departureLocation || !$arrivalLocation) {
            throw new NotFoundHttpException('User or Location not found.');
        }

        // Create the new Request entity
        $request = new Request();
        $request->setUser($user)
                ->setDepartureLocation($departureLocation)
                ->setArrivalLocation($arrivalLocation)
                ->setRequest_date($requestDate)
                ->setStatus(REQUEST_STATUS::from($status));

        // Persist the entity to the database
        $this->entityManager->persist($request);
        $this->entityManager->flush();

        return $request;
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
