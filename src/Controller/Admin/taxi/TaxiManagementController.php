<?php

namespace App\Controller\Admin\taxi;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\RequestService;
use App\Enum\REQUEST_STATUS;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\Request as HttpRequest;
use Symfony\Component\HttpFoundation\RequestStack;
use App\Enum\RIDE_STATUS;
use App\Service\RideService;

class TaxiManagementController extends AbstractController
{
    private RequestService $requestService;
    private RideService $rideService;

    public function __construct(RequestService $requestService, RideService $rideService)
    {
        $this->requestService = $requestService;
        $this->rideService = $rideService; 
    }

    #[Route('/admin/taxi-management', name: 'admin_taxi_management')]
    public function index(): Response
    {
        // Fetch all requests using the RequestService
        $availableRequests = $this->requestService->getRealyAllRequest();
        $availableRides = $this->rideService->getAllRides();

        $ridesWithDetails = array_map(function ($ride) {
            return [
                'id' => $ride->getIdRide(), // Ride ID
                'pickupLocation' => $ride->getRequest()->getDepartureLocation() ? $ride->getRequest()->getDepartureLocation()->getAddress() : 'Unknown', // Pickup address
                'duration' => $ride->getDuration(), // Ride duration
               // 'driverName' => $ride->getDriver() ? $ride->getDriver()->getname : 'Unknown', // Driver name
                'dropoffLocation' => $ride->getRequest()->getArrivalLocation() ? $ride->getRequest()->getArrivalLocation()->getAddress() : 'Unknown', // Destination address
                'price' => $ride->getPrice(), // Ride price
                'status' => $ride->getStatus()->value, // Ride status
                'distance' => $ride->getDistance(), // Ride distance
                'userName' =>$ride->getRequest()->getUser()->getName(), // User name
            ];
        }, $availableRides);

        $requestsWithDetails = array_map(function ($request) {
            return [
                'id' => $request->getIdRequest(),
                'pickupLocation' => $request->getDepartureLocation() ? $request->getDepartureLocation()->getAddress() : 'Unknown',
                'dropoffLocation' => $request->getArrivalLocation() ? $request->getArrivalLocation()->getAddress() : 'Unknown',
                'time' => $request->getRequestDate() ? $request->getRequestDate()->format('Y-m-d H:i:s') : 'Unknown',
                'status' => $request->getStatus() instanceof REQUEST_STATUS ? $request->getStatus()->value : 'Unknown',
                'userName' => $request->getUser() ? $request->getUser()->getName() : 'Unknown',
            ];
        }, $availableRequests);

        return $this->render('back-office/taxi/taxi-management.html.twig', [
            'availableRequests' => $requestsWithDetails,
            'availableRides' => $ridesWithDetails,
        ]);
    }

    #[Route('/request/delete/{id}', name: 'delete_request_backoffice', methods: ['POST'])]
    public function delete(int $id, RequestService $requestService): JsonResponse
    {
        try {
            $requestService->deleteRequest($id);
            return new JsonResponse(['status' => 'success']);
        } catch (NotFoundHttpException $e) {
            return new JsonResponse(['status' => 'error', 'message' => 'An error occurred while deleting the request.'], 404);
        }
    }

    #[Route('/ride/delete/{id}', name: 'delete_ride_backoffice', methods: ['POST'])]
    public function deleteRide(int $id): JsonResponse
    {
        try {
            // Call the deleteRide method from the RideService
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
