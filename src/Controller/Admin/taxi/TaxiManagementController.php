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
use Doctrine\ORM\Tools\Pagination\Paginator;
use Knp\Component\Pager\PaginatorInterface;


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
    public function index(HttpRequest $request): Response
    {
        // Get search parameters from request
        $filter = $request->query->get('filter');
        $search = $request->query->get('search');
        $status = $request->query->get('status');
        $sort = $request->query->get('sort'); // Get sort parameter

        // Fetch all requests and rides
        $availableRequests = $this->requestService->getRealyAllRequest();
        $availableRides = $this->rideService->getAllRides();

        // Apply search filters to requests
        if ($search) {
            $availableRequests = array_filter($availableRequests, function ($request) use ($search) {
                return stripos($request->getUser()->getName(), $search) !== false ||
                       stripos($request->getDepartureLocation()->getAddress(), $search) !== false ||
                       stripos($request->getArrivalLocation()->getAddress(), $search) !== false ||
                       stripos($request->getStatus()->value, $search) !== false ||
                       stripos($request->getIdRequest(), $search) !== false;
            });
        }

        // Apply search filters to rides
        if ($search) {
            $availableRides = array_filter($availableRides, function ($ride) use ($search) {
                return stripos($ride->getRequest()->getUser()->getName(), $search) !== false ||
                       stripos($ride->getRequest()->getDepartureLocation()->getAddress(), $search) !== false ||
                       stripos($ride->getRequest()->getArrivalLocation()->getAddress(), $search) !== false;
            });
        }

        // Filter requests by status
        if ($status) {
            $availableRequests = array_filter($availableRequests, function ($request) use ($status) {
                return $request->getStatus()->value === $status;
            });
        }

        // Filter rides by status
        if ($status) {
            $availableRides = array_filter($availableRides, function ($ride) use ($status) {
                return $ride->getStatus()->value === $status;
            });
        }

        // Sort requests and rides if sort parameter is provided
        $sortDir = $request->query->get('direction', 'asc');
        if ($sort) {
            // Sort requests by request date
            usort($availableRequests, function ($a, $b) use ($sortDir) {
                $comparison = $a->getRequestDate() <=> $b->getRequestDate();
                return $sortDir === 'desc' ? -$comparison : $comparison;
            });

            // Sort rides by request date (through the associated request)
            if (!empty($availableRides)) {
                usort($availableRides, function ($a, $b) use ($sortDir) {
                    $comparison = $a->getRequest()->getRequestDate() <=> $b->getRequest()->getRequestDate();
                    return $sortDir === 'desc' ? -$comparison : $comparison;
                });
            }
        }

        // Paginate requests using Pagerfanta
        $requestPaginator = new \Pagerfanta\Pagerfanta(new \Pagerfanta\Adapter\ArrayAdapter($availableRequests));
        $requestPaginator->setMaxPerPage(2);
        $requestPaginator->setCurrentPage($request->query->getInt('page_requests', 1));  // Get 'page_requests' from the URL, default to 1

        // Paginate rides using Pagerfanta
        $ridePaginator = new \Pagerfanta\Pagerfanta(new \Pagerfanta\Adapter\ArrayAdapter($availableRides));
        $ridePaginator->setMaxPerPage(2);
        $ridePaginator->setCurrentPage($request->query->getInt('page_rides', 1));  // Get 'page_rides' from the URL, default to 1

        // Map paginated data to include additional details
        $ridesWithDetails = [];
        foreach ($ridePaginator->getCurrentPageResults() as $ride) {
            $ridesWithDetails[] = [
                'id' => $ride->getIdRide(),
                'pickupLocation' => $ride->getRequest()->getDepartureLocation() ? $ride->getRequest()->getDepartureLocation()->getAddress() : 'Unknown',
                'duration' => $ride->getDuration(),
                'dropoffLocation' => $ride->getRequest()->getArrivalLocation() ? $ride->getRequest()->getArrivalLocation()->getAddress() : 'Unknown',
                'price' => $ride->getPrice(),
                'status' => $ride->getStatus()->value,
                'distance' => $ride->getDistance(),
                'userName' => $ride->getRequest()->getUser()->getName(),
                'pickupLat' => $ride->getRequest()->getDepartureLocation() ? $ride->getRequest()->getDepartureLocation()->getLatitude() : null,
                'pickupLng' => $ride->getRequest()->getDepartureLocation() ? $ride->getRequest()->getDepartureLocation()->getLongitude() : null,
                'dropoffLat' => $ride->getRequest()->getArrivalLocation() ? $ride->getRequest()->getArrivalLocation()->getLatitude() : null,
                'dropoffLng' => $ride->getRequest()->getArrivalLocation() ? $ride->getRequest()->getArrivalLocation()->getLongitude() : null,
                'time' => $ride->getRequest()->getRequestDate() ? $ride->getRequest()->getRequestDate()->format('Y-m-d H:i:s') : 'Unknown',
            ];
        }

        $requestsWithDetails = [];
        foreach ($requestPaginator->getCurrentPageResults() as $request) {
            $requestsWithDetails[] = [
                'id' => $request->getIdRequest(),
                'pickupLocation' => $request->getDepartureLocation() ? $request->getDepartureLocation()->getAddress() : 'Unknown',
                'dropoffLocation' => $request->getArrivalLocation() ? $request->getArrivalLocation()->getAddress() : 'Unknown',
                'pickupLat' => $request->getDepartureLocation() ? $request->getDepartureLocation()->getLatitude() : null,
                'pickupLng' => $request->getDepartureLocation() ? $request->getDepartureLocation()->getLongitude() : null,
                'dropoffLat' => $request->getArrivalLocation() ? $request->getArrivalLocation()->getLatitude() : null,
                'dropoffLng' => $request->getArrivalLocation() ? $request->getArrivalLocation()->getLongitude() : null,
                'time' => $request->getRequestDate() ? $request->getRequestDate()->format('Y-m-d H:i:s') : 'Unknown',
                'status' => $request->getStatus() instanceof REQUEST_STATUS ? $request->getStatus()->value : 'Unknown',
                'userName' => $request->getUser() ? $request->getUser()->getName() : 'Unknown',
            ];
        }

        return $this->render('back-office/taxi/taxi-management.html.twig', [
            'availableRequests' => $requestsWithDetails,
            'availableRides' => $ridesWithDetails,
            'paginationRequests' => $requestPaginator, // Corrected variable name
            'paginationRides' => $ridePaginator,
        ]);
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
