<?php

namespace App\Controller\front\taxi;

use App\Entity\Request;
use App\Entity\User;
use App\Service\RequestService;
use App\Service\RideService;
use App\Service\AzureTTSService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\Request as HttpRequest;
use Symfony\Component\Security\Core\Security;

#[Route('/services/taxi')]
class TaxiController extends AbstractController
{
    private $entityManager;
    private $requestService;
    private $rideService;
    private $azureTTSService;
    private $security;

    public function __construct(EntityManagerInterface $entityManager, RequestService $requestService, RideService $rideService, AzureTTSService $azureTTSService, Security $security)
    {
        $this->entityManager = $entityManager;
        $this->requestService = $requestService;
        $this->rideService = $rideService;
        $this->azureTTSService = $azureTTSService;
        $this->security = $security;
    }

  #[Route('/taxi/management', name: 'app_taxi_management')]
public function index(): Response
{
    $user = $this->security->getUser(); // Get the currently logged-in user

    if (!$user) {
        throw $this->createNotFoundException('User not found.');
    }
    $requests = $this->requestService->getRequestsForUser($user->getId_user());
    $rides = $this->rideService->getRidesByUser($user->getId_user());

    $requestData = [];
    foreach ($requests as $request) {
        $pickup = $request->getDepartureLocation() ? $request->getDepartureLocation()->getAddress() : 'Adresse de départ inconnue';
        $destination = $request->getArrivalLocation() ? $request->getArrivalLocation()->getAddress() : 'Adresse de destination inconnue';

        // Générer l'audio texte pour Request
        $speechText = "Votre départ est à {$pickup} et votre arrivée est à {$destination}.";
        $audioContent = $this->azureTTSService->synthesizeSpeech($speechText);

        // Sauvegarder l'audio dans public/audio/request_{id}.mp3
        $projectDir = $this->getParameter('kernel.project_dir');
        $audioDir = $projectDir . '/public/audio';
        if (!is_dir($audioDir)) {
            mkdir($audioDir, 0777, true);
        }
        $audioFilename = $audioDir . '/request_' . $request->getIdRequest() . '.mp3';
        file_put_contents($audioFilename, $audioContent);

        $requestData[] = [
            'id' => $request->getIdRequest(),
            'pickupLocation' => $pickup,
            'pickupLatitude' => $request->getDepartureLocation() ? $request->getDepartureLocation()->getLatitude() : null,
            'pickupLongitude' => $request->getDepartureLocation() ? $request->getDepartureLocation()->getLongitude() : null,
            'destination' => $destination,
            'destinationLatitude' => $request->getArrivalLocation() ? $request->getArrivalLocation()->getLatitude() : null,
            'destinationLongitude' => $request->getArrivalLocation() ? $request->getArrivalLocation()->getLongitude() : null,
            'requestTime' => $request->getRequestDate()->format('Y-m-d H:i:s'),
            'status' => $request->getStatus()->value,
            'audioPath' => '/' . $audioFilename, // à utiliser dans Twig pour lire l'audio
        ];
    }

    $rideData = [];
    foreach ($rides as $ride) {
        $pickupLocation = $ride->getRequest()->getDepartureLocation() ? $ride->getRequest()->getDepartureLocation()->getAddress() : 'Unknown';
        $destination = $ride->getRequest()->getArrivalLocation() ? $ride->getRequest()->getArrivalLocation()->getAddress() : 'Unknown';

        // Générer l'audio texte pour Ride
        $speechText = "La course a démarré à {$pickupLocation} et se termine à {$destination}.";
        $audioContent = $this->azureTTSService->synthesizeSpeech($speechText);

        // Sauvegarder l'audio dans public/audio/ride_{id}.mp3
        $projectDir = $this->getParameter('kernel.project_dir');
        $audioDir = $projectDir . '/public/audio';
        if (!is_dir($audioDir)) {
            mkdir($audioDir, 0777, true);
        }
        $audioFilename = $audioDir . '/ride_' . $ride->getIdRide() . '.mp3';
        file_put_contents($audioFilename, $audioContent);

        $rideData[] = [
            'id' => $ride->getIdRide(),
            'pickupLocation' => $pickupLocation,
            'duration' => $ride->getDuration(),
            'driverName' => $ride->getDriver() ? $ride->getDriver()->getUser()->getName() : 'Unknown',
            'destination' => $destination,
            'price' => $ride->getPrice(),
            'status' => $ride->getStatus()->value,
            'distance' => $ride->getDistance(),
            'driverPhone' => $ride->getDriver() ? $ride->getDriver()->getUser()->getPhone_number() : 'Unknown',
            'audioPath' => '/' . $audioFilename, // à utiliser dans Twig pour lire l'audio
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
