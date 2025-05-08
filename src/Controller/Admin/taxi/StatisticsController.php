<?php

namespace App\Controller\Admin\taxi;

use App\Repository\RequestRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\RequestService;
use App\Service\RideService;

class StatisticsController extends AbstractController
{
    private RequestService $requestService;
    private RideService $rideService;

    public function __construct(RequestService $requestService, RideService $rideService)
    {
        $this->requestService = $requestService;
        $this->rideService = $rideService; 
    }

    #[Route('/admin/taxi/statistics', name: 'admin_taxi_statistics')]
    public function index(): Response
    {
        $totalRequests = $this->requestService->countRequests(); 
        $totalRides = $this->rideService->getTotalRidesCount();
       $totalRidesCost = $this->rideService->calculateTotalRidesPrice();
       $avgDuration = $this->rideService->calculateAverageRideDuration();
     
        $rideStatusCounts = $this->rideService->getRidesStatusCount();
        
        return $this->render('back-office/taxi/statistics.html.twig', [
            'controller_name' => 'StatisticsController',
            'total_requests' => $totalRequests,
            'total_rides' => $totalRides,
            'total_rides_cost' => $totalRidesCost,
            'avg_duration' => $avgDuration,
            'ride_status_counts' => $rideStatusCounts,
        ]);
    }
}