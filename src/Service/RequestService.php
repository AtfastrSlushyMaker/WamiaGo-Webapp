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
    private $locationService;

    public function __construct(
        EntityManagerInterface $entityManager,
        RequestRepository $requestRepository,
       
    ) {
        $this->entityManager = $entityManager;
        $this->requestRepository = $requestRepository;
      
    }

    public function createRequest(
        User $user,
        Location $pickupLocation,
        Location $arrivalLocation
    ): Request {
        // Create the request
        $request = new Request();
        $request->setUser($user)
            ->setDepartureLocation($pickupLocation)
            ->setArrivalLocation($arrivalLocation)
            ->setRequest_date(new \DateTime()) // System date
            ->setStatus(REQUEST_STATUS::PENDING); // Default status
    
        // Persist the request
        $this->entityManager->persist($request);
        $this->entityManager->flush();
    
        return $request;
    }

    public function getRequestById(int $id): ?Request
    {
        return $this->requestRepository->find($id);
    }

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

    public function getRequestsForUser(int $userId): array
    {
        return $this->requestRepository->findBy(['user' => $userId]);
    }
}
