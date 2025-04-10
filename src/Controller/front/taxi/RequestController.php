<?php

namespace App\Controller\front\taxi;

use App\Entity\User;
use App\Entity\Location;
use App\Entity\Request as TaxiRequest;
use App\Enum\REQUEST_STATUS;
use App\Service\RequestService;
use App\Service\LocationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class RequestController extends AbstractController
{
    private RequestService $requestService;
    private EntityManagerInterface $entityManager;

    public function __construct(RequestService $requestService, EntityManagerInterface $entityManager)
    {
        $this->requestService = $requestService;
        $this->entityManager = $entityManager;
    }

    #[Route('/taxi/request', name: 'app_taxi_request', methods: ['GET', 'POST'])]
    public function createRequest(Request $request, LocationService $locationService): Response
    {
        if ($request->isMethod('POST')) {
            // Retrieve form data
            $pickupAddress = $request->request->get('pickupLocation');
            $pickupLat = (float)$request->request->get('pickupLat');
            $pickupLng = (float)$request->request->get('pickupLng');
            $arrivalAddress = $request->request->get('arrivalLocation');
            $arrivalLat = (float)$request->request->get('arrivalLat');
            $arrivalLng = (float)$request->request->get('arrivalLng');
            $userId = 114; // Static user ID for now (replace with session-based user retrieval later)

            try {
                // Retrieve the user using the EntityManager
                $user = $this->entityManager->getRepository(User::class)->find($userId);
                if (!$user) {
                    throw $this->createNotFoundException('User not found.');
                }

                // Create the pickup and arrival locations
                $pickupLocation = $locationService->createLocation($pickupAddress, $pickupLat, $pickupLng);
                $arrivalLocation = $locationService->createLocation($arrivalAddress, $arrivalLat, $arrivalLng);

                // Create the request
                $this->requestService->createRequest($user, $pickupLocation, $arrivalLocation);

                $this->addFlash('success', 'Your request has been created successfully!');
                return $this->redirectToRoute('app_taxi_management');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Error creating request: ' . $e->getMessage());
            }
        }

        return $this->render('front/taxi/request.html.twig');
    }
}
