<?php

namespace App\Service;

use App\Entity\Request;
use App\Entity\User;
use App\Entity\Location;
use App\Enum\REQUEST_STATUS;
use App\Repository\RequestRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Doctrine\ORM\Query;

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


public function searchRequests(?string $search = null, ?string $status = null, ?string $sort = null): array
{
    $qb = $this->entityManager->createQueryBuilder();
    $qb->select('r')
       ->from(Request::class, 'r')
       ->leftJoin('r.user', 'u')
       ->leftJoin('r.departureLocation', 'dl')
       ->leftJoin('r.arrivalLocation', 'al');

    // Apply search filter if provided
    if ($search) {
        $qb->andWhere(
            $qb->expr()->orX(
                $qb->expr()->like('r.id_request', ':search'),
                $qb->expr()->like('u.name', ':search'),
                $qb->expr()->like('dl.address', ':search'),
                $qb->expr()->like('al.address', ':search'),
                $qb->expr()->like('r.status', ':search')
            )
        )->setParameter('search', '%' . $search . '%');
    }

    // Apply status filter if provided separately
    if ($status) {
        $qb->andWhere('r.status = :status')
           ->setParameter('status', $status);
    }

    // Apply sorting (only by date)
    if ($sort === 'date_asc') {
        $qb->orderBy('r.requestDate', 'ASC');
    } else {
        $qb->orderBy('r.requestDate', 'DESC'); // Default sort
    }

    return $qb->getQuery()->getResult();
}

public function filterRequestsByStatus(string $status): array
{
    return $this->requestRepository->findBy(['status' => $status]);
}
public function sortRequests(string $sortBy, string $sortOrder = 'ASC'): array
{
    $qb = $this->entityManager->createQueryBuilder();
    $qb->select('r')
       ->from(Request::class, 'r')
       ->leftJoin('r.user', 'u'); // Join with the user entity for sorting by name

    // Validate sort order
    $sortOrder = strtoupper($sortOrder);
    if (!in_array($sortOrder, ['ASC', 'DESC'])) {
        throw new \InvalidArgumentException('Invalid sort order. Use "ASC" or "DESC".');
    }

    // Apply sorting
    $validSortFields = ['request_date', 'user_name', 'id_request'];
    if (!in_array($sortBy, $validSortFields)) {
        throw new \InvalidArgumentException('Invalid sort field. Allowed fields: ' . implode(', ', $validSortFields));
    }

    // Map sortBy to actual database fields
    $fieldMapping = [
        'request_date' => 'r.request_date',
        'user_name' => 'u.name',
        'id_request' => 'r.id_request',
    ];

    $qb->orderBy($fieldMapping[$sortBy], $sortOrder);

    return $qb->getQuery()->getResult();
}

public function countRequests(): int
{
    return $this->requestRepository->count([]);
}

}
