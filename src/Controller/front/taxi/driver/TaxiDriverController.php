<?php

namespace App\Controller\front\taxi\driver;

use App\Entity\Request;
use App\Entity\Ride;
use App\Enum\REQUEST_STATUS;
use App\Enum\RIDE_STATUS;
use App\Service\RequestService;
use App\Service\RideService;
use App\Entity\User;
use App\Entity\Driver;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/driver')]
class TaxiDriverController extends AbstractController
{
    public function __construct(
        private RequestService $requestService,
        private RideService $rideService,
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger, // Add logger
        private \Symfony\Component\HttpFoundation\RequestStack $requestStack // Add RequestStack
        
    ) {}
    

    #[Route('/dashboard', name: 'app_taxi_driver_dashboard')]
    public function dashboard(): Response
    {
        // Fetch all requests using the RequestService
        $availableRequests = $this->requestService->getAllRequests();

        // Map requests to include detailed information
        $requestsWithDetails = array_map(function ($request) {
            return [
                'id' => $request->getIdRequest(), // Corrected to fetch the request ID
                'pickupLocation' => $request->getDepartureLocation() ? $request->getDepartureLocation()->getAddress() : 'Unknown',
                'dropoffLocation' => $request->getArrivalLocation() ? $request->getArrivalLocation()->getAddress() : 'Unknown',
                'time' => $request->getRequestDate() ? $request->getRequestDate()->format('Y-m-d H:i:s') : 'Unknown', // Added null check
                'status' => $request->getStatus() instanceof REQUEST_STATUS ? $request->getStatus()->value : 'Unknown', // Added enum check
                'userName' => $request->getUser() ? $request->getUser()->getName() : 'Unknown', // Fetch user name
            ];
        }, $availableRequests);

        // Render the template and pass the requests with details
        return $this->render('front/taxi/driver/taxi-management-driver.html.twig', [
            'availableRequests' => $requestsWithDetails,
        ]);
    }


    #[Route('/accept-request/{id}', name: 'app_accept_request', methods: ['POST'])]
public function acceptRequest(int $id): JsonResponse
{
    try {
        $this->logger->info("Accepting request with ID: $id");
        
        $data = json_decode($this->requestStack->getCurrentRequest()?->getContent(), true);
        $data = json_decode($this->requestStack->getCurrentRequest()->getContent(), true);
        $duration = isset($data['duration']) ? (int)$data['duration'] : null;
        
        if (!$duration || $duration <= 0) {
            throw new \Exception('Please provide a valid duration.');
        }
        
        $this->logger->info("Duration received: $duration minutes");

        // Find the request
        $request = $this->entityManager->getRepository(Request::class)->find($id);
        
        if (!$request) {
            throw new \Exception('Request not found with ID: ' . $id);
        }
        
        // Find driver with ID 1 directly
        $driver = $this->entityManager->getRepository(Driver::class)->find(1);
        
        if (!$driver) {
            throw new \Exception('Driver with ID 1 not found in the database.');
        }
        
        $this->logger->info("Driver found with ID: 1");
        
        // Update request status
        $request->setStatus(REQUEST_STATUS::ACCEPTED);
        $this->entityManager->flush();
        
        // Create the ride with duration
        $ride = $this->rideService->createRide($request, $driver, $duration);
        
        return new JsonResponse([
            'success' => true,
            'message' => 'Request accepted and ride created successfully.',
            'rideId' => $ride->getId_ride(),
        ]);
    } catch (\Exception $e) {
        $this->logger->error("Error: " . $e->getMessage());
        return new JsonResponse([
            'success' => false,
            'message' => $e->getMessage(),
        ], 400);
    }
}
}
