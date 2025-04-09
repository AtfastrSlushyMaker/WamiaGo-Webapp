<?php

namespace App\Controller\front\taxi;

use App\Service\LocationService;
use App\Service\RequestService;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class RequestController extends AbstractController
{
    private $requestService;
    private $locationService;
    private $entityManager;

    public function __construct(RequestService $requestService, LocationService $locationService, EntityManagerInterface $entityManager)
    {
        $this->requestService = $requestService;
        $this->locationService = $locationService;
        $this->entityManager = $entityManager;
    }

    // Dans RequestController.php

    #[Route('/taxi/request', name: 'app_taxi_request', methods: ['GET', 'POST'])]
    public function index(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            // Récupérer les données du formulaire
            $pickupLocation = $request->request->get('pickupLocation');
            $arrivalLocation = $request->request->get('arrivalLocation');
            $pickupLat = (float)$request->request->get('pickupLat');
            $pickupLng = (float)$request->request->get('pickupLng');
            $arrivalLat = (float)$request->request->get('arrivalLat');
            $arrivalLng = (float)$request->request->get('arrivalLng');
            $userId = 114;  // ID utilisateur statique
            $status = 'pending';  // Statut par défaut

            // Récupérer l'entité User à partir de l'ID utilisateur
            $user = $this->entityManager->getRepository(User::class)->find($userId);
            if (!$user) {
                $this->addFlash('error', 'User not found.');
                return $this->redirectToRoute('app_taxi_request');
            }

            // Créer les locations (pickup et arrival)
            try {
                $departureLocation = $this->locationService->createLocation($pickupLocation, $pickupLat, $pickupLng);
                $arrivalLocation = $this->locationService->createLocation($arrivalLocation, $arrivalLat, $arrivalLng);

                // Créer la demande avec les deux locations et l'utilisateur
                $newRequest = $this->requestService->createRequest(
                    $user,
                    $departureLocation->getIdLocation(),
                    $pickupLat, $pickupLng,
                    $arrivalLocation->getIdLocation(),
                    $arrivalLat, $arrivalLng,
                    new \DateTime(),
                    $status
                );

                // Rediriger vers la page de gestion des taxis
                return $this->redirectToRoute('app_taxi_management');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Error: ' . $e->getMessage());
            }
        }

        return $this->render('front/taxi/request.html.twig');
    }
    #[Route('/taxi/management', name: 'app_taxi_management')]
    public function taxiManagement(): Response
    {
        return $this->render('front/taxi/taxi-management.html.twig');
    }
}
