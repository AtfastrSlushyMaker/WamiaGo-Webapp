<?php

namespace App\Controller\front\taxi;

use App\Entity\Location;
use App\Entity\Request as TaxiRequest;
use App\Service\RequestService;
use App\Service\LocationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class RequestUpdateController extends AbstractController
{
    #[Route('/request/update/{id}', name: 'update_request', methods: ['GET', 'POST'])]
    public function update(
        int $id,
        Request $httpRequest,
        EntityManagerInterface $entityManager,
        RequestService $requestService,
        LocationService $locationService,
        ValidatorInterface $validator
    ): Response {
        // Fetch the request entity by ID
        $taxiRequest = $entityManager->getRepository(TaxiRequest::class)->find($id);

        if (!$taxiRequest) {
            throw $this->createNotFoundException('The request does not exist.');
        }

        // Initialize validation errors array
        $validationErrors = [];
        
        if ($httpRequest->isMethod('POST')) {
            try {
                // Get form data
                $pickupLocation = trim($httpRequest->request->get('pickupLocation', ''));
                $pickupLat = $httpRequest->request->get('pickupLat', '');
                $pickupLng = $httpRequest->request->get('pickupLng', '');
                $arrivalLocation = trim($httpRequest->request->get('arrivalLocation', ''));
                $arrivalLat = $httpRequest->request->get('arrivalLat', '');
                $arrivalLng = $httpRequest->request->get('arrivalLng', '');
                
                // Basic form validation
                if (empty($pickupLocation)) {
                    $validationErrors['pickupLocation'] = 'Pickup location is required';
                }
                if (empty($pickupLat) || empty($pickupLng)) {
                    $validationErrors['pickupLocation'] = 'Pickup location is required';
                }
                if (empty($arrivalLocation)) {
                    $validationErrors['arrivalLocation'] = 'Arrival location is required';
                }
                if (empty($arrivalLat) || empty($arrivalLng)) {
                    $validationErrors['arrivalLocation'] = 'Arrival location is required';
                }
                
                // Check if locations are the same
               else  if ($pickupLocation === $arrivalLocation && 
                    $pickupLat === $arrivalLat && 
                    $pickupLng === $arrivalLng) {
                    $validationErrors['arrivalLocation'] = 'Departure and arrival locations cannot be the same';
                }
                
                // Proceed if basic validation passes
                if (empty($validationErrors)) {
                    // Create or update location objects
                    $pickupLocationObj = $locationService->createLocation(
                        $pickupLocation, 
                        (float)$pickupLat, 
                        (float)$pickupLng
                    );
                    
                    $arrivalLocationObj = $locationService->createLocation(
                        $arrivalLocation, 
                        (float)$arrivalLat, 
                        (float)$arrivalLng
                    );
                    
                    // Create a new request entity to validate changes
                    $updatedRequest = new TaxiRequest();
                    $updatedRequest->setUser($taxiRequest->getUser())
                        ->setDepartureLocation($pickupLocationObj)
                        ->setArrivalLocation($arrivalLocationObj)
                        ->setStatus($taxiRequest->getStatus())
                        ->setRequestDate($taxiRequest->getRequestDate());
                    
                    // Validate with Assert constraints
                    $constraints = $validator->validate($updatedRequest);
                    
                    if (count($constraints) > 0) {
                        // Process validation errors
                        foreach ($constraints as $constraint) {
                            $propertyPath = $constraint->getPropertyPath();
                            $message = $constraint->getMessage();
                            
                            // Map property paths to form fields
                            if (strpos($propertyPath, 'departureLocation') !== false) {
                                $validationErrors['pickupLocation'] = $message;
                            } elseif (strpos($propertyPath, 'arrivalLocation') !== false) {
                                $validationErrors['arrivalLocation'] = $message;
                            } else {
                                $validationErrors[$propertyPath] = $message;
                            }
                        }
                    } else {
                        // Update the request with validated data
                        $requestService->updateRequest(
                            $id,
                            $taxiRequest->getUser(),
                            $pickupLocationObj,
                            $arrivalLocationObj,
                            $taxiRequest->getStatus()
                        );
                        
                        $this->addFlash('success', 'The request has been updated successfully.');
                        return $this->redirectToRoute('app_taxi_management');
                    }
                }
            } catch (\Exception $e) {
                $this->addFlash('error', 'An error occurred: ' . $e->getMessage());
            }
        }

        // Get current locations for initial display or after validation failure
        $locations = $requestService->getRequestLocations($id);
        
        return $this->render('front/taxi/request-update.html.twig', [
            'request' => $taxiRequest,
            'requestId' => $id,
            'pickupLocation' => $locations['pickupLocation'],
            'arrivalLocation' => $locations['arrivalLocation'],
            'validationErrors' => $validationErrors
        ]);
    }
}