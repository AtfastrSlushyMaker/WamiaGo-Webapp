<?php

namespace App\Controller\Admin\taxi;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\RequestService;
use App\Enum\REQUEST_STATUS;

class TaxiManagementController extends AbstractController
{
    private RequestService $requestService;

    public function __construct(RequestService $requestService)
    {
        $this->requestService = $requestService;
    }

    #[Route('/admin/taxi-management', name: 'admin_taxi_management')]
    public function index(): Response
    {
        // Render the Twig template

        $availableRequests = $this->requestService->getRealyAllRequest();
        return $this->render('back-office/taxi/taxi-management.html.twig');
    }

    public function dashboard(): Response
    {
        // Fetch all requests using the RequestService
        $availableRequests = $this->requestService->getRealyAllRequest();

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

        return $this->render('back-office/taxi/taxi-management.html.twig', [
            'availableRequests' => $requestsWithDetails,
        ]);
    }
}
