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
use Symfony\Component\Validator\Validator\ValidatorInterface;

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
    public function createRequest(Request $request, LocationService $locationService, ValidatorInterface $validator): Response
    {
        // Initialize variables
        $formData = [
            'pickupLocation' => '',
            'pickupLat' => '',
            'pickupLng' => '',
            'arrivalLocation' => '',
            'arrivalLat' => '',
            'arrivalLng' => '',
        ];
        $validationErrors = [];
        
        if ($request->isMethod('POST')) {
            // Retrieve form data
            $formData = [
                'pickupLocation' => trim($request->request->get('pickupLocation', '')),
                'pickupLat' => $request->request->get('pickupLat', ''),
                'pickupLng' => $request->request->get('pickupLng', ''),
                'arrivalLocation' => trim($request->request->get('arrivalLocation', '')),
                'arrivalLat' => $request->request->get('arrivalLat', ''),
                'arrivalLng' => $request->request->get('arrivalLng', ''),
            ];
            
            // Basic form validation
            if (empty($formData['pickupLocation'])) {
                $validationErrors['pickupLocation'] = 'Pickup location is required';
            }
            if (empty($formData['pickupLat']) || empty($formData['pickupLng'])) {
                $validationErrors['pickupLocation'] = 'Pickup location is required';
            }
            if (empty($formData['arrivalLocation'])) {
                $validationErrors['arrivalLocation'] = 'Arrival location is required';
            }
            if (empty($formData['arrivalLat']) || empty($formData['arrivalLng'])) {
                $validationErrors['arrivalLocation'] = 'Arrival location is required';
            }
            
            // Check if pickup and arrival are the same
           else  if ( 
                $formData['pickupLat'] === $formData['arrivalLat'] && 
                $formData['pickupLng'] === $formData['arrivalLng']) {
                $validationErrors['arrivalLocation'] = 'Departure and arrival locations cannot be the same';
            }
            
            // Proceed if basic validation passes
            if (empty($validationErrors)) {
                try {
                    // Get user
                    $userId = 114; // Static user ID for demo
                    $user = $this->entityManager->getRepository(User::class)->find($userId);
                    if (!$user) {
                        throw $this->createNotFoundException('User not found');
                    }
                    
                    // Create location objects
                    $pickupLocation = $locationService->createLocation(
                        $formData['pickupLocation'], 
                        (float)$formData['pickupLat'], 
                        (float)$formData['pickupLng']
                    );
                    
                    $arrivalLocation = $locationService->createLocation(
                        $formData['arrivalLocation'], 
                        (float)$formData['arrivalLat'], 
                        (float)$formData['arrivalLng']
                    );
                    
                    // Create request entity
                    $taxiRequest = new TaxiRequest();
                    $taxiRequest->setUser($user)
                        ->setDepartureLocation($pickupLocation)
                        ->setArrivalLocation($arrivalLocation)
                        ->setStatus(REQUEST_STATUS::PENDING)
                        ->setRequestDate(new \DateTime());
                    
                    // Validate entity using Assert constraints
                    $constraints = $validator->validate($taxiRequest);
                    
                    if (count($constraints) > 0) {
                        // Convert constraint violations to error messages
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
                        // Save valid request
                        $this->entityManager->persist($taxiRequest);
                        $this->entityManager->flush();
                        
                        $this->addFlash('success', 'Your taxi request has been created successfully!');
                        return $this->redirectToRoute('app_taxi_management');
                    }
                } catch (\Exception $e) {
                    $this->addFlash('error', 'An error occurred: ' . $e->getMessage());
                }
            }
        }
        
        // Render the form with validation errors and form data
        return $this->render('front/taxi/request.html.twig', [
            'formData' => $formData,
            'validationErrors' => $validationErrors
        ]);
    }
}