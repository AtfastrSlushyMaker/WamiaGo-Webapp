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
use Symfony\Component\Validator\Validator\ValidatorInterface;

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
        $driver = $this->entityManager->getRepository(Driver::class)->find(1);
        
        $activeRides = $this->rideService->getActiveRidesByDriver($driver);// Fetch driver with ID 1

       
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


        $ridesWithDetails = array_map(function ($ride) {
            return [
                'id' => $ride->getId_ride(), // Corrected to fetch the ride ID
              
                'pickupLocation' => $ride->getRequest()->getDepartureLocation() ? $ride->getRequest()->getDepartureLocation()->getAddress() : 'Unknown',
                'dropoffLocation' => $ride->getRequest()->getArrivalLocation() ? $ride->getRequest()->getArrivalLocation()->getAddress() : 'Unknown',
                'distance' => $ride->getDistance(),
                'duration' => $ride->getDuration(),
                'price' => $ride->getPrice(),
                'status' => $ride->getStatus() instanceof RIDE_STATUS ? $ride->getStatus()->value : 'Unknown', // Convert enum to string
                'userName' => $ride->getRequest()->getUser() ? $ride->getRequest()->getUser()->getName() : 'Unknown',
            ];
        }, $activeRides);

        
        return $this->render('front/taxi/driver/taxi-management-driver.html.twig', [
            'availableRequests' => $requestsWithDetails,
            'activeRides' => $ridesWithDetails,
        ]);
    }


    #[Route('/accept-request/{id}', name: 'app_accept_request', methods: ['POST'])]
    public function acceptRequest(int $id, ValidatorInterface $validator): JsonResponse
    {
        try {
            $this->logger->info("Accepting request with ID: $id");
    
            $requestData = $this->requestStack->getCurrentRequest();
            $data = json_decode($requestData->getContent(), true);
    
            if (!isset($data['duration'])) {
                throw new \Exception('The duration field is required.');
            }
    
            // Create Ride and set duration for validation
            $ride = new Ride();
            $ride->setDuration((int)$data['duration']);
    
            // Validate the ride entity (especially duration constraints)
            $errors = $validator->validate($ride);
    
            if (count($errors) > 0) {
                $errorMessages = [];
                foreach ($errors as $error) {
                    $errorMessages[] = $error->getPropertyPath() . ': ' . $error->getMessage();
                }
                return new JsonResponse([
                    'success' => false,
                    'message' => implode(' | ', $errorMessages),
                ], 422);
            }
    
            $this->logger->info("Duration validated: {$data['duration']} minutes");
    
            // Fetch the request
            $request = $this->entityManager->getRepository(Request::class)->find($id);
    
            if (!$request) {
                throw new \Exception("Request not found with ID: $id");
            }
    
            // Hardcoded driver (ID 1)
            $driver = $this->entityManager->getRepository(Driver::class)->find(1);
    
            if (!$driver) {
                throw new \Exception("Driver with ID 1 not found.");
            }
    
            // Update request status
            $request->setStatus(REQUEST_STATUS::ACCEPTED);
            $this->entityManager->flush();
    
            // Create and persist the ride (now validated)
            $ride = $this->rideService->createRide($request, $driver, (int)$data['duration']);
    
            return new JsonResponse([
                'success' => true,
                'message' => 'Request accepted and ride created successfully.',
                'rideId' => $ride->getId_ride(),
            ]);
        } catch (\Exception $e) {
            $this->logger->error("Error in acceptRequest: " . $e->getMessage());
            return new JsonResponse([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
    


#[Route('/ride/delete/{id}', name: 'app_delete_ride', methods: ['DELETE'])]
public function deleteRide(int $id): JsonResponse
{
    try {
        // Find the ride
        $ride = $this->entityManager->getRepository(Ride::class)->find($id);
        
        if (!$ride) {
            throw new \Exception('Ride not found with ID: ' . $id);
        }
        
        // Check if the ride belongs to the driver (if you implement authentication)
        // Add this check later when you implement user sessions
        
        // Delete the ride
        $this->entityManager->remove($ride);
        $this->entityManager->flush();
        
        return new JsonResponse([
            'success' => true,
            'message' => 'Ride deleted successfully.'
        ]);
    } catch (\Exception $e) {
        return new JsonResponse([
            'success' => false,
            'message' => $e->getMessage()
        ], 400);
    }
}


#[Route('/update-ride-duration/{id}', name: 'app_update_ride_duration', methods: ['POST'])]
public function updateRideDuration(int $id, \Symfony\Component\HttpFoundation\Request $request): JsonResponse
{
    try {
        // Get the duration from request body
        $data = json_decode($request->getContent(), true);
        $duration = isset($data['duration']) ? (int)$data['duration'] : null;
        
        if (!$duration) {
            throw new \Exception('Duration is required.');
        }
        
        // Call the service method to update the duration
        $ride = $this->rideService->updateRideDuration($id, $duration);
        
        return new JsonResponse([
            'success' => true,
            'message' => 'Ride duration updated successfully.',
            'rideId' => $ride->getId_ride(),
        ]);
    } catch (\Exception $e) {
        $this->logger->error("Error updating ride duration: " . $e->getMessage());
        return new JsonResponse([
            'success' => false,
            'message' => $e->getMessage(),
        ], 400);
    }
}
}
