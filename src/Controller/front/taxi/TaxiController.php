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

#[Route('/services/taxi')]
class TaxiController extends AbstractController
{
    private $entityManager;
    private $requestService;

    public function __construct(EntityManagerInterface $entityManager, RequestService $requestService)
    {
        $this->entityManager = $entityManager;
        $this->requestService = $requestService;
    }

    #[Route('/taxi/management', name: 'app_taxi_management')]
    public function index(): Response
    {
        // Fetch user with static ID 114
        $user = $this->entityManager->getRepository(User::class)->find(114);

        // Handle case where user with ID 114 doesn't exist
        if (!$user) {
            throw $this->createNotFoundException('User with ID 114 not found.');
        }

        // Use RequestService to fetch requests for the static user ID (114)
        $requests = $this->requestService->getRequestsForUser($user->getIdUser()); // Use getIdUser()

        // Prepare data for rendering in the template
        $requestData = [];

        foreach ($requests as $request) {
            $requestData[] = [
                'id' => $request->getIdRequest(), // Request ID
                'pickupLocation' => $request->getDepartureLocation() ? $request->getDepartureLocation()->getAddress() : 'Unknown', // Pickup address
                'pickupLatitude' => $request->getDepartureLocation() ? $request->getDepartureLocation()->getLatitude() : null, // Pickup Latitude
                'pickupLongitude' => $request->getDepartureLocation() ? $request->getDepartureLocation()->getLongitude() : null, // Pickup Longitude
                'destination' => $request->getArrivalLocation() ? $request->getArrivalLocation()->getAddress() : 'Unknown', // Destination address
                'destinationLatitude' => $request->getArrivalLocation() ? $request->getArrivalLocation()->getLatitude() : null, // Destination Latitude
                'destinationLongitude' => $request->getArrivalLocation() ? $request->getArrivalLocation()->getLongitude() : null, // Destination Longitude
                'requestTime' => $request->getRequestDate()->format('Y-m-d H:i:s'), // Request time
                'status' => $request->getStatus()->value, // Request status
            ];
        }

        return $this->render('front/taxi/taxi-management.html.twig', [
            'requests' => $requestData,
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
        // Fetch the request by ID
        $request = $this->entityManager->getRepository(Request::class)->find($id);

        // Handle case where the request doesn't exist
        if (!$request) {
            throw $this->createNotFoundException('Request not found.');
        }

        // Pass the request data to the template
        return $this->render('front/taxi/request-update.html.twig', [
            'request' => $request,
        ]);
    }
}
