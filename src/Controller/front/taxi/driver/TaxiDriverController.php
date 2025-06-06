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
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use App\Service\GeminiService;
use Symfony\Component\Security\Core\Security;
use App\Repository\DriverRepository;


#[Route('/driver')]
class TaxiDriverController extends AbstractController

{
    //private Security $security; // Add security

    public function __construct(
        private RequestService $requestService,
        private RideService $rideService,
        private EntityManagerInterface $entityManager, // Add logger
        private \Symfony\Component\HttpFoundation\RequestStack $requestStack, // Add RequestStack // Add security
        private DriverRepository $driverRepository, 
        private readonly Security $security,
        private readonly LoggerInterface $logger// Add DriverRepository
        
        
    ) {// Initialize the driver repository
    }
    

    #[Route('/dashboard', name: 'app_taxi_driver_dashboard')]
    public function dashboard(): Response
    {
        // Fetch all requests using the RequestService
        $availableRequests = $this->requestService->getAllRequests();
        //$driver = $this->entityManager->getRepository(Driver::class)->find(1);
        $user = $this->getUser();
        $driver = $this->driverRepository->findOneBy(['user' => $user]);
        
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
            'chatMessages' => [],
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
            $user = $this->getUser();
$driver = $this->driverRepository->findOneBy(['user' => $user]);
        
        if (!$driver) {
            throw new \Exception('Driver with ID 1 not found in the database.');
        }
        
        $this->logger->info("Driver found with ID: 1");
        
        // Update request status
        $request->setStatus(REQUEST_STATUS::ACCEPTED);
        $this->entityManager->flush();
        
        // Create the ride with duration
        $ride = $this->rideService->createRide($request, $driver, $duration);
       // $request = $this->requestService->deleteRequest($id); 
        
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



#[Route('/chat', name: 'app_taxi_driver_chat', methods: ['POST'])]
public function chat(\Symfony\Component\HttpFoundation\Request $request, GeminiService $geminiService): JsonResponse
{
    try {
        // Get the user's message from the request
        $data = json_decode($request->getContent(), true);
        $userMessage = $data['message'] ?? '';

        if (empty($userMessage)) {
            return new JsonResponse([
                'success' => false,
                'message' => 'No message provided.',
            ], 400);
        }

        // Send the message to the Gemini chatbot and get the response
        $botResponse = $geminiService->sendMessage($userMessage);

        // Return the bot's response as a JSON response
        return new JsonResponse([
            'success' => true,
            'botMessage' => $botResponse, // Send back the bot's response
        ]);
    } catch (ClientExceptionInterface $e) {
        $this->logger->error("Client error: " . $e->getMessage());
        return new JsonResponse([
            'success' => false,
            'message' => 'Client error: ' . $e->getMessage(),
        ], 400);
    } catch (ServerExceptionInterface $e) {
        $this->logger->error("Server error: " . $e->getMessage());
        return new JsonResponse([
            'success' => false,
            'message' => 'Server error: ' . $e->getMessage(),
        ], 500);
    } catch (TransportExceptionInterface $e) {
        $this->logger->error("Transport error: " . $e->getMessage());
        return new JsonResponse([
            'success' => false,
            'message' => 'Transport error: ' . $e->getMessage(),
        ], 503);
    } catch (\Exception $e) {
        $this->logger->error("General error: " . $e->getMessage());
        return new JsonResponse([
            'success' => false,
            'message' => 'An error occurred: ' . $e->getMessage(),
        ], 500);
    }
}

















}
