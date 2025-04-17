<?php

namespace App\Controller\Api;

use App\Entity\Location;
use App\Repository\LocationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api')]
class LocationApiController extends AbstractController
{
    #[Route('/locations', name: 'api_locations', methods: ['GET'])]
    public function getLocations(LocationRepository $locationRepository): JsonResponse
    {
        $locations = $locationRepository->findAll();
        
        $data = [];
        foreach ($locations as $location) {
            $data[] = [
                'id' => $location->getIdLocation(),
                'address' => $location->getAddress(),
                'latitude' => $location->getLatitude(),
                'longitude' => $location->getLongitude()
            ];
        }
        
        return $this->json($data);
    }
}