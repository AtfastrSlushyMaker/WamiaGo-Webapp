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
        
        $request = new Request();
        $request->setUser($user)
            ->setDepartureLocation($pickupLocation)
            ->setArrivalLocation($arrivalLocation)
            ->setRequest_date(new \DateTime()) 
            ->setStatus(REQUEST_STATUS::PENDING); 
    
    
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

    public function updateRequest(
        int $requestId,
        User $user,
        Location $pickupLocation,
        Location $arrivalLocation,
        REQUEST_STATUS $status
    ): Request {
        
        $request = $this->requestRepository->find($requestId);
        if (!$request) {
            throw new NotFoundHttpException('Request not found.');
        }
    
        
        if (empty($pickupLocation->getAddress()) || 
            !is_numeric($pickupLocation->getLatitude()) || 
            !is_numeric($pickupLocation->getLongitude())) {
            throw new \InvalidArgumentException('Pickup location must have valid address and coordinates');
        }
    
        
        if (empty($arrivalLocation->getAddress()) || 
            !is_numeric($arrivalLocation->getLatitude()) || 
            !is_numeric($arrivalLocation->getLongitude())) {
            throw new \InvalidArgumentException('Arrival location must have valid address and coordinates');
        }
    
        
        $newPickupLocation = new Location();
        $newPickupLocation->setAddress($pickupLocation->getAddress())
            ->setLatitude((float)$pickupLocation->getLatitude())
            ->setLongitude((float)$pickupLocation->getLongitude());
    
        $newArrivalLocation = new Location();
        $newArrivalLocation->setAddress($arrivalLocation->getAddress())
            ->setLatitude((float)$arrivalLocation->getLatitude())
            ->setLongitude((float)$arrivalLocation->getLongitude());
    
    
        $this->entityManager->persist($newPickupLocation);
        $this->entityManager->persist($newArrivalLocation);
       
       
        $request->setUser($user)
            ->setDepartureLocation($newPickupLocation)
            ->setArrivalLocation($newArrivalLocation)
            ->setStatus($status)
            ->setRequest_date(new \DateTime());
    
        $this->entityManager->persist($request);
        $this->entityManager->flush();
    
        return $request;
    }
    public function getRequestLocations(int $requestId): array
{

   
    $request = $this->requestRepository->find($requestId);

    if (!$request) {
        throw new NotFoundHttpException('Request not found.');
    }


    $pickupLocation = $request->getDepartureLocation();
    $arrivalLocation = $request->getArrivalLocation();


    return [
        'pickupLocation' => $pickupLocation ? $pickupLocation->getAddress() : 'Unknown',
        'arrivalLocation' => $arrivalLocation ? $arrivalLocation->getAddress() : 'Unknown',
    ];
}
public function getAllRequests(): array
{
    return $this->requestRepository->findBy(['status' => REQUEST_STATUS::PENDING]);
}

public function getRealyAllRequest():array
{
    return $this->requestRepository->findAll();
}

public function acceptRequest(int $requestId): Request
{
    
    $request = $this->requestRepository->find($requestId);

    if (!$request) {
        throw new NotFoundHttpException('Request not found.');
    }

    
    $request->setStatus(REQUEST_STATUS::ACCEPTED);

   
    $this->entityManager->persist($request);
    $this->entityManager->flush();

    return $request;
}
}
