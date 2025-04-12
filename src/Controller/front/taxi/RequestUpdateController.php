<?php

namespace App\Controller\front\taxi;

use App\Entity\Location;
use App\Entity\Request as TaxiRequest;
use App\Service\RequestService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class RequestUpdateController extends AbstractController
{
    #[Route('/request/update/{id}', name: 'update_request', methods: ['GET', 'POST'])]
    public function update(
        int $id,
        Request $httpRequest,
        EntityManagerInterface $entityManager,
        RequestService $requestService
    ): Response {
        // Fetch the request entity by ID
        $request = $entityManager->getRepository(TaxiRequest::class)->find($id);

        if (!$request) {
            throw $this->createNotFoundException('The request does not exist.');
        }

        if ($httpRequest->isMethod('POST')) {
            try {
                // Get form data and validate coordinates
                $pickupLocation = $httpRequest->request->get('pickupLocation');
                $pickupLat = (float)$httpRequest->request->get('pickupLat');
                $pickupLng = (float)$httpRequest->request->get('pickupLng');
                $arrivalLocation = $httpRequest->request->get('arrivalLocation');
                $arrivalLat = (float)$httpRequest->request->get('arrivalLat');
                $arrivalLng = (float)$httpRequest->request->get('arrivalLng');

                // Validate coordinates
                if (!is_numeric($pickupLat) || !is_numeric($pickupLng) || 
                    !is_numeric($arrivalLat) || !is_numeric($arrivalLng)) {
                    throw new \InvalidArgumentException('Invalid coordinates provided');
                }

                // Create Location objects
                $pickupLocationObj = new Location();
                $pickupLocationObj->setAddress($pickupLocation)
                    ->setLatitude($pickupLat)
                    ->setLongitude($pickupLng);

                $arrivalLocationObj = new Location();
                $arrivalLocationObj->setAddress($arrivalLocation)
                    ->setLatitude($arrivalLat)
                    ->setLongitude($arrivalLng);

                // Update the request
                $requestService->updateRequest(
                    $id,
                    $request->getUser(),
                    $pickupLocationObj,
                    $arrivalLocationObj,
                    $request->getStatus()
                );

                $this->addFlash('success', 'The request has been updated successfully.');
                return $this->redirectToRoute('app_taxi_management');

            } catch (\InvalidArgumentException $e) {
                $this->addFlash('error', $e->getMessage());
                return $this->redirectToRoute('update_request', ['id' => $id]);
            }
        }

        // Get current locations for GET request
        $locations = $requestService->getRequestLocations($id);

        return $this->render('front/taxi/request-update.html.twig', [
            'request' => $request,
            'requestId' => $id,
            'pickupLocation' => $locations['pickupLocation'],
            'arrivalLocation' => $locations['arrivalLocation'],
        ]);
    }
}