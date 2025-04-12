<?php

namespace App\Controller\front\taxi\driver;

use App\Entity\Request;
use App\Entity\Ride;
use App\Enum\REQUEST_STATUS;
use App\Enum\RIDE_STATUS;
use App\Service\RequestService;
use App\Service\RideService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/driver')]
class TaxiDriverController extends AbstractController
{
    public function __construct(
        private RequestService $requestService,
        private RideService $rideService
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
}