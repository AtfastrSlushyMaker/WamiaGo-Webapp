<?php

namespace App\Controller\Admin\Api;

use App\Entity\BicycleStation;
use App\Repository\BicycleStationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

#[Route('/admin/bicycle/api', name: 'admin_bicycle_api_')]
class BicycleStationApiController extends AbstractController
{
    private BicycleStationRepository $stationRepository;

    public function __construct(BicycleStationRepository $stationRepository)
    {
        $this->stationRepository = $stationRepository;
    }

    #[Route('/stations', name: 'stations', methods: ['GET'])]
    public function getStations(): JsonResponse
    {
        $stations = $this->stationRepository->findAll();
        
        $formattedStations = [];
        
        foreach ($stations as $station) {
            $formattedStations[] = [
                'id' => $station->getIdStation(),
                'name' => $station->getName(),
                'lat' => $station->getLocation() ? $station->getLocation()->getLatitude() : null,
                'lng' => $station->getLocation() ? $station->getLocation()->getLongitude() : null,
                'status' => $station->getStatus()->value,
                'address' => $station->getLocation() ? $station->getLocation()->getAddress() : 'No address',
                'availableBikes' => $station->getAvailableBikes(),
                'totalDocks' => $station->getTotalDocks(),
                'chargingDocks' => method_exists($station, 'getChargingDocks') ? $station->getChargingDocks() : 0
            ];
        }
        
        return new JsonResponse($formattedStations);
    }
    
    #[Route('/locations', name: 'locations', methods: ['GET'])]
    public function getLocations(): JsonResponse
    {
        $stations = $this->stationRepository->findAll();
        $locations = [];
        
        foreach ($stations as $station) {
            if ($station->getLocation()) {
                $location = $station->getLocation();
                $locations[] = [
                    'id' => $location->getId(),
                    'latitude' => $location->getLatitude(),
                    'longitude' => $location->getLongitude(),
                    'address' => $location->getAddress(),
                    'stationId' => $station->getIdStation(),
                    'stationName' => $station->getName()
                ];
            }
        }
        
        return new JsonResponse($locations);
    }
}