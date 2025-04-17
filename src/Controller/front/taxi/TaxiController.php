<?php

namespace App\Controller\front\taxi;

use App\Entity\Request;
use App\Entity\User;
use App\Service\RequestService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use App\Service\RideService;
use App\Enum\RIDE_STATUS;
use Symfony\Component\HttpFoundation\Request as HttpRequest;


#[Route('/services/taxi')]
class TaxiController extends AbstractController
{
    private $entityManager;
    private $requestService;
    private $rideService;

    public function __construct(EntityManagerInterface $entityManager, RequestService $requestService, RideService $rideService)
    {
        $this->entityManager = $entityManager;
        $this->requestService = $requestService;
        $this->rideService = $rideService;
    }

    #[Route('/taxi/management', name: 'app_taxi_management')]
    public function index(): Response
    {
       
        $user = $this->entityManager->getRepository(User::class)->find(114);

       
        if (!$user) {
            throw $this->createNotFoundException('User with ID 114 not found.');
        }

      
        $requests = $this->requestService->getRequestsForUser($user->getIdUser());
        $rides = $this->rideService->getRidesByUser($user->getIdUser());

        
        $requestData = [];

        foreach ($requests as $request) {
            $requestData[] = [
                'id' => $request->getIdRequest(), 
                'pickupLocation' => $request->getDepartureLocation() ? $request->getDepartureLocation()->getAddress() : 'Unknown', 
                'pickupLatitude' => $request->getDepartureLocation() ? $request->getDepartureLocation()->getLatitude() : null, 
                'pickupLongitude' => $request->getDepartureLocation() ? $request->getDepartureLocation()->getLongitude() : null, 
                'destination' => $request->getArrivalLocation() ? $request->getArrivalLocation()->getAddress() : 'Unknown', 
                'destinationLatitude' => $request->getArrivalLocation() ? $request->getArrivalLocation()->getLatitude() : null, 
                'destinationLongitude' => $request->getArrivalLocation() ? $request->getArrivalLocation()->getLongitude() : null, 
                'requestTime' => $request->getRequestDate()->format('Y-m-d H:i:s'), 
                'status' => $request->getStatus()->value, 
            ];
        }


        $rideData = [];

        foreach ($rides as $ride) {
            $rideData[] = [
                'id' => $ride->getIdRide(), 
                'pickupLocation' => $ride->getRequest()->getDepartureLocation() ? $ride->getRequest()->getDepartureLocation()->getAddress() : 'Unknown', 
                'duration' => $ride->getDuration(), 
               // 'driverName' => $ride->getDriver() ? $ride->getDriver()->getname : 'Unknown', 
                'destination' => $ride->getRequest()->getArrivalLocation() ? $ride->getRequest()->getArrivalLocation()->getAddress() : 'Unknown', 
                'price' => $ride->getPrice(), 
                'status' => $ride->getStatus()->value, 
                'distance' => $ride->getDistance(), 
            ];
        }



        return $this->render('front/taxi/taxi-management.html.twig', [
            'requests' => $requestData,
            'rides' => $rideData, 
        ]);
    }

    #[Route('/request/delete/{id}', name: 'delete_request', methods: ['POST'])]
    public function delete(int $id, RequestService $requestService): JsonResponse
    {
        try {
            $requestService->deleteRequest($id);
            return new JsonResponse(['status' => 'success']);
        } catch (NotFoundHttpException $e) {
            return new JsonResponse(['status' => 'error', 'message' => 'An error occurred while deleting the request.'], 404);
        }
    }

    #[Route('/request', name: 'request_page')]
    public function request()
    {
        return $this->render('front/taxi/request.html.twig');
    }
    
    #[Route('/request/update/{id}', name: 'request_update', methods: ['GET'])]
    public function requestUpdate(int $id): Response
    {
        
        $request = $this->entityManager->getRepository(Request::class)->find($id);

        
        if (!$request) {
            throw $this->createNotFoundException('Request not found.');
        }

        
        return $this->render('front/taxi/request-update.html.twig', [
            'request' => $request,
        ]);
    }

    #[Route('/ride/delete/{id}', name: 'delete_ride', methods: ['POST'])]
    public function deleteRide(int $id): JsonResponse
    {
        try {
           
            $this->rideService->deleteRide($id);
    
            return new JsonResponse([
                'status' => 'success',
                'message' => 'Ride deleted successfully.',
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

   
   
  
}