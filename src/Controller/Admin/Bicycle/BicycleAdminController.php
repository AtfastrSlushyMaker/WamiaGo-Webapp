<?php

namespace App\Controller\Admin\Bicycle;

use App\Entity\Bicycle;
use App\Entity\BicycleStation;
use App\Entity\BicycleRental;
use App\Entity\Location;
use App\Enum\BICYCLE_STATUS;
use App\Enum\BICYCLE_STATION_STATUS;
use App\Form\BicycleType;
use App\Form\BicycleStationType;
use App\Service\BicycleService;
use App\Service\BicycleStationService;
use App\Service\BicycleRentalService;
use App\Service\LocationService;
use App\Service\ExportService;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use App\entity\User;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

//#[IsGranted('ROLE_ADMIN')]
#[Route('/admin/bicycle')]
class BicycleAdminController extends AbstractController
{
    private $entityManager;
    private $bicycleService;
    private $stationService;
    private $rentalService;
    private $locationService;
    private $logger;
    private $paginator;
    private $exportService;

    public function __construct(
        EntityManagerInterface $entityManager,
        BicycleService $bicycleService,
        BicycleStationService $stationService,
        BicycleRentalService $rentalService,
        LocationService $locationService,
        LoggerInterface $logger,
        PaginatorInterface $paginator,
        ExportService $exportService
    ) {
        $this->entityManager = $entityManager;
        $this->bicycleService = $bicycleService;
        $this->stationService = $stationService;
        $this->rentalService = $rentalService;
        $this->locationService = $locationService;
        $this->logger = $logger;
        $this->paginator = $paginator;
        $this->exportService = $exportService;
    }    #[Route('/add', name: 'admin_bicycle_add', methods: ['GET', 'POST'])]    public function create(Request $request, EntityManagerInterface $entityManager): Response
    {        // Log the request information for debugging
        $this->logger->info('Bicycle creation request received', [
            'method' => $request->getMethod(),
            'request_data' => $request->request->all(),
            'query_data' => $request->query->all(),
            'has_content' => !empty($request->getContent()),
            'content_type' => $request->headers->get('Content-Type'),
            'request_uri' => $request->getRequestUri(),
            'base_url' => $request->getSchemeAndHttpHost(),
            'server' => $request->server->all()
        ]);
        
        // Special debug message to track request lifecycle
        $this->logger->debug('BICYCLE CREATION STARTED', [
            'timestamp' => (new \DateTime())->format('Y-m-d H:i:s.u'),
            'client_ip' => $request->getClientIp()
        ]);        $bicycle = new Bicycle();
        // Set default values
        $bicycle->setLastUpdated(new \DateTime());
        $bicycle->setStatus(BICYCLE_STATUS::AVAILABLE);
        $bicycle->setBatteryLevel(0); // Set default battery level to 0%
        $bicycle->setRangeKm(50); // Set default range to 50km
        
        $form = $this->createForm(BicycleType::class, $bicycle);
        $form->handleRequest($request);
        
        // Log form state
        $this->logger->info('Form state after handleRequest', [
            'is_submitted' => $form->isSubmitted(),
            'method' => $request->getMethod(),
            'form_errors' => $this->getFormErrors($form)
        ]);
        
        // For AJAX requests, return the form HTML for GET or JSON response for POST
        if ($request->isXmlHttpRequest()) {
            if ($request->isMethod('GET')) {
                return $this->render('back-office/bicycle/Bicycle/_bicycle_form.html.twig', [
                    'form' => $form->createView(),
                    'isNew' => true
                ]);
            }
            
            // Check if form was submitted and is valid
            if ($form->isSubmitted()) {
                if ($form->isValid()) {
                    try {
                        // Ensure the bicycle has a last updated date
                        if (!$bicycle->getLastUpdated()) {
                            $bicycle->setLastUpdated(new \DateTime());
                        }
                        
                        // Persist to database
                        $entityManager->persist($bicycle);
                        $this->logger->info('Persisting bicycle to database');
                        $entityManager->flush();
                        
                        // Return success JSON
                        return new JsonResponse([
                            'success' => true,
                            'message' => 'Bicycle created successfully.',
                            'bicycleId' => $bicycle->getIdBike()
                        ]);
                    } catch (\Exception $e) {
                        $this->logger->error('Error creating bicycle: ' . $e->getMessage(), [
                            'exception' => $e->getTraceAsString()
                        ]);
                        
                        // Return error JSON
                        return new JsonResponse([
                            'success' => false,
                            'message' => 'Error creating bicycle: ' . $e->getMessage(),
                            'errors' => $this->getFormErrors($form)
                        ], 400);
                    }
                } else {
                    // Form not valid, return errors
                    return new JsonResponse([
                        'success' => false,
                        'message' => 'Bicycle could not be created. Please check the form for errors.',
                        'errors' => $this->getFormErrors($form)
                    ], 400);
                }
            }
        }
        
        // Non-AJAX form submission handling
        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                try {
                    // Ensure the bicycle has a last updated date
                    if (!$bicycle->getLastUpdated()) {
                        $bicycle->setLastUpdated(new \DateTime());
                    }
                    
                    // Persist to database
                    $entityManager->persist($bicycle);
                    $this->logger->info('Persisting bicycle to database');
                    $entityManager->flush();
                    
                    $this->addFlash('success', 'Bicycle created successfully.');
                    $this->logger->info('Bicycle created successfully', [
                        'bicycle_id' => $bicycle->getIdBike(),
                        'battery_level' => $bicycle->getBatteryLevel(),
                        'range_km' => $bicycle->getRangeKm(),
                        'status' => $bicycle->getStatus()->value
                    ]);
                } catch (\Exception $e) {
                    $this->logger->error('Error creating bicycle: ' . $e->getMessage(), [
                        'exception' => $e->getTraceAsString()
                    ]);
                    $this->addFlash('error', 'Error creating bicycle: ' . $e->getMessage());
                }
            } else {
                // Form was submitted but not valid
                $this->addFlash('error', 'Bicycle could not be created. Please check the form for errors.');
                $this->logger->error('Form validation failed when creating bicycle', [
                    'form_errors' => $this->getFormErrors($form)
                ]);
            }
            
            // Check if tab parameter was passed in request
            $tab = $request->request->get('tab', 'bicycles');
            
            // Special debug message to track request completion
            $this->logger->debug('BICYCLE CREATION COMPLETED', [
                'timestamp' => (new \DateTime())->format('Y-m-d H:i:s.u'),
                'tab' => $tab,
                'is_form_valid' => $form->isSubmitted() && $form->isValid(),
                'redirect_to' => 'admin_bicycle_rentals'
            ]);
            
            // Redirect back to the bicycles tab and make sure we stay on it
            return $this->redirectToRoute('admin_bicycle_rentals', ['tab' => $tab]);
        }
        
        // For direct access to the add route (not AJAX), redirect to the bicycle page
        return $this->redirectToRoute('admin_bicycle_rentals', ['tab' => 'bicycles']);
    }
    
    #[Route('/{id}/edit-form', name: 'admin_bicycle_edit_form', methods: ['GET'])]    public function editBicycleForm(int $id, Request $request, EntityManagerInterface $em): Response {
        // Log the edit form request
        $this->logger->info('Bicycle edit form request received', [
            'id' => $id,
            'method' => $request->getMethod()
        ]);

        // Find the bicycle by ID
        $bicycle = $em->getRepository(Bicycle::class)->find($id);
        if (!$bicycle) {
            // Return a 404 response if the bicycle was not found
            return new Response('Bicycle not found', 404);
        }
        
        // Create the form with the Bicycle entity
        $form = $this->createForm(BicycleType::class, $bicycle, [
            'bicycleId' => $bicycle->getIdBike()
        ]);

        // Render just the form template for AJAX
        return $this->render('back-office/bicycle/Bicycle/_bicycle_form.html.twig', [
            'form' => $form->createView(),
            'bicycle' => $bicycle,
            'isNew' => false
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_bicycle_edit', methods: ['GET', 'POST'])]    public function editBicycle(
        int $id, 
        Request $request, 
        EntityManagerInterface $em,
        ValidatorInterface $validator
    ): Response {
        // Log the edit request
        $this->logger->info('Bicycle edit request received', [
            'id' => $id,
            'method' => $request->getMethod(),
            'request_data' => $request->request->all(),
            'query_data' => $request->query->all()
        ]);

        // Find the bicycle by ID
        $bicycle = $em->getRepository(Bicycle::class)->find($id);
        if (!$bicycle) {
            $this->addFlash('error', 'Bicycle not found.');
            return $this->redirectToRoute('admin_bicycle_rentals', ['tab' => 'bicycles']);
        }
    
        // Ensure the status is set
        if (!$bicycle->getStatus()) {
            $bicycle->setStatus(BICYCLE_STATUS::AVAILABLE);
        }
    
        // Create the form with the Bicycle entity
        $form = $this->createForm(BicycleType::class, $bicycle, [
            'bicycleId' => $bicycle->getIdBike()
        ]);
    
        // Handle the form submission
        $form->handleRequest($request);
        
        // Log form state
        $this->logger->info('Edit form state after handleRequest', [
            'is_submitted' => $form->isSubmitted(),
            'is_valid' => $form->isValid(),
            'form_errors' => $this->getFormErrors($form)
        ]);
        
        // For AJAX requests
        if ($request->isXmlHttpRequest()) {
            if ($request->isMethod('GET')) {
                return $this->render('back-office/bicycle/Bicycle/_bicycle_form.html.twig', [
                    'form' => $form->createView(),
                    'bicycle' => $bicycle,
                    'isNew' => false
                ]);
            }
            
            if ($form->isSubmitted()) {
                if ($form->isValid()) {
                    try {
                        // Always update last updated timestamp
                        $bicycle->setLastUpdated(new \DateTime());
                        
                        // Save to database
                        $em->flush();
                        
                        // Return success JSON
                        return new JsonResponse([
                            'success' => true,
                            'message' => 'Bicycle updated successfully.',
                            'bicycleId' => $bicycle->getIdBike()
                        ]);
                    } catch (\Exception $e) {
                        // Log the error
                        $this->logger->error('Error updating bicycle: ' . $e->getMessage());
                        
                        // Return error JSON
                        return new JsonResponse([
                            'success' => false,
                            'message' => 'Error updating bicycle: ' . $e->getMessage(),
                            'errors' => $this->getFormErrors($form)
                        ], 400);
                    }
                } else {
                    // Form validation failed
                    return new JsonResponse([
                        'success' => false,
                        'message' => 'Bicycle could not be updated. Please check the form for errors.',
                        'errors' => $this->getFormErrors($form)
                    ], 400);
                }
            }
        }
    
        // For regular form submissions
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                // Always update last updated timestamp
                $bicycle->setLastUpdated(new \DateTime());
                
                // Save the updated bicycle to the database
                $em->flush();
    
                // Success message
                $this->addFlash('success', 'Bicycle updated successfully!');
                $this->logger->info('Bicycle updated successfully', [
                    'bicycle_id' => $bicycle->getIdBike()
                ]);
    
                // Check if tab parameter was passed in request
                $tab = $request->request->get('tab', 'bicycles');
                $this->logger->info('Redirecting after edit form processing', [
                    'tab' => $tab
                ]);
                
                // Redirect to bicycle rentals page and stay on bicycles tab
                return $this->redirectToRoute('admin_bicycle_rentals', ['tab' => $tab]);
            } catch (\Exception $e) {
                // Log the error and display a flash message
                $this->logger->error('Error saving bicycle: ' . $e->getMessage(), [
                    'exception' => $e->getTraceAsString()
                ]);
                $this->addFlash('error', 'Error saving bicycle: ' . $e->getMessage());
            }
        } else if ($form->isSubmitted()) {
            // Form was submitted but not valid
            $this->addFlash('error', 'Bicycle could not be updated. Please check the form for errors.');
            $this->logger->error('Edit form validation failed', [
                'form_errors' => $this->getFormErrors($form)
            ]);
              // Get tab from request
            $tab = $request->request->get('tab', 'bicycles');
            
            // Redirect back to the bicycles tab with the error message
            return $this->redirectToRoute('admin_bicycle_rentals', ['tab' => $tab]);
        }
          // If it's a GET request or the form wasn't submitted yet
        return $this->redirectToRoute('admin_bicycle_rentals', ['tab' => 'bicycles']);
    }      #[Route('/{id}/data', name: 'admin_bicycle_data', methods: ['GET'])]
    public function bicycleData(int $id): JsonResponse
    {
        try {
            $bicycle = $this->bicycleService->getBicycle($id);
            
            if (!$bicycle) {
                return new JsonResponse(['error' => 'Bicycle not found'], 404);
            }
            
            // Return a complete set of data in a predictable format
            $response = [
                'idBike' => $bicycle->getIdBike(),
                'status' => $bicycle->getStatus(),
                'batteryLevel' => $bicycle->getBatteryLevel(),
                'rangeKm' => $bicycle->getRangeKm(),
                'stationId' => $bicycle->getBicycleStation() ? $bicycle->getBicycleStation()->getIdStation() : null,
                'lastUpdated' => $bicycle->getLastUpdated()->format('Y-m-d H:i:s'),
                'success' => true
            ];
            
            if ($bicycle->getBicycleStation()) {
                $response['bicycleStation'] = [
                    'idStation' => $bicycle->getBicycleStation()->getIdStation(),
                    'name' => $bicycle->getBicycleStation()->getName()
                ];
            }
            
            return new JsonResponse($response);
        } catch (\Exception $e) {
            $this->logger->error('Error getting bicycle data: ' . $e->getMessage());
            return new JsonResponse([
                'error' => 'Error retrieving bicycle data',
                'message' => $e->getMessage(),
                'success' => false
            ], 500);
        }
    }
      #[Route('/get-details', name: 'admin_bicycle_get_details', methods: ['GET'])]
    public function getBicycleDetails(Request $request): JsonResponse
    {
        $id = $request->query->get('id');
        $bicycle = $this->bicycleService->getBicycle((int)$id);
        
        if (!$bicycle) {
            return new JsonResponse(['error' => 'Bicycle not found'], 404);
        }
        
        // Return bicycle data as JSON
        return new JsonResponse([
            'id' => $bicycle->getIdBike(),
            'status' => $bicycle->getStatus()->value,
            'batteryLevel' => $bicycle->getBatteryLevel(),
            'rangeKm' => $bicycle->getRangeKm(),
            'stationId' => $bicycle->getBicycleStation() ? $bicycle->getBicycleStation()->getIdStation() : null,
            'lastUpdated' => $bicycle->getLastUpdated()->format('Y-m-d H:i:s')
        ]);
    }
      #[Route('/{id}/json', name: 'admin_bicycle_json', methods: ['GET'])]
    public function bicycleJson(int $id): JsonResponse
    {
        $bicycle = $this->bicycleService->getBicycle($id);
        
        if (!$bicycle) {
            return new JsonResponse(['error' => 'Bicycle not found'], 404);
        }
        
        return new JsonResponse([
            'idBike' => $bicycle->getIdBike(),
            'status' => [
                'value' => $bicycle->getStatus()->value,
                'name' => $bicycle->getStatus()->name
            ],
            'batteryLevel' => $bicycle->getBatteryLevel(),
            'rangeKm' => $bicycle->getRangeKm(),
            'bicycleStation' => $bicycle->getBicycleStation() ? [
                'idStation' => $bicycle->getBicycleStation()->getIdStation(),
                'name' => $bicycle->getBicycleStation()->getName()
            ] : null,
            'lastUpdated' => $bicycle->getLastUpdated()->format('Y-m-d H:i:s')
        ]);
    }    #[Route('/delete', name: 'admin_bicycle_delete', methods: ['POST'])]
    public function deleteBicycle(Request $request): Response
    {
        $bicycleId = $request->request->get('bicycleId');
        $bicycle = $this->entityManager->getRepository(Bicycle::class)->find((int)$bicycleId);
          // Get tab parameter either from the request or referring URL
        $tab = $request->request->get('tab');
        
        if (!$tab) {
            // Check if the referer contains a tab parameter
            $referer = $request->headers->get('referer');
            if ($referer && preg_match('/tab=([^&]+)/', $referer, $matches)) {
                $tab = $matches[1];
            } else {
                $tab = 'bicycles'; // Default tab
            }
        }
        
        if (!$bicycle) {
            $this->addFlash('error', 'Bicycle not found');
        } else {
            try {
                $this->entityManager->remove($bicycle);
                $this->entityManager->flush();
                $this->addFlash('success', 'Bicycle deleted successfully');
            } catch (\Exception $e) {
                $this->logger->error('Error deleting bicycle: ' . $e->getMessage());
                $this->addFlash('error', 'Error deleting bicycle: ' . $e->getMessage());
            }
        }
        
        // Redirect back to the appropriate tab
        return $this->redirectToRoute('admin_bicycle_rentals', ['tab' => $tab]);
    }    #[Route('/change-status', name: 'admin_bicycle_change_status', methods: ['GET', 'POST'])]
    public function changeBicycleStatus(Request $request): Response
    {
        // Support both GET and POST parameters
        $bicycleId = $request->request->get('bicycleId') ?? $request->query->get('id');
        $statusValue = $request->request->get('status') ?? $request->query->get('status');
        
        try {
            $bicycle = $this->bicycleService->getBicycle((int)$bicycleId);
            
            if (!$bicycle) {
                $this->addFlash('error', 'Bicycle not found');
            } else {
                $status = \App\Enum\BICYCLE_STATUS::from($statusValue);
                $this->bicycleService->changeBicycleStatus($bicycle, $status);
                $this->addFlash('success', sprintf('Bicycle status changed to %s', $status->value));
            }
        } catch (\Exception $e) {
            $this->logger->error('Error changing bicycle status: ' . $e->getMessage());
            $this->addFlash('error', 'Error changing bicycle status: ' . $e->getMessage());
        }
        
        return $this->redirectToRoute('admin_bicycle_dashboard', ['tab' => 'bicycles']);
    }
      #[Route('/schedule-maintenance', name: 'admin_bicycle_schedule_maintenance', methods: ['POST'])]
    public function scheduleMaintenance(Request $request): Response
    {
        $bicycleIds = $request->request->get('bicycleIds', []);
        $maintenanceType = $request->request->get('maintenanceType');
        $notes = $request->request->get('notes');
        
        $success = 0;
        $failures = 0;
        
        foreach ($bicycleIds as $id) {
            try {
                $bicycle = $this->bicycleService->getBicycle((int)$id);
                if ($bicycle) {
                    $this->bicycleService->changeBicycleStatus($bicycle, \App\Enum\BICYCLE_STATUS::MAINTENANCE);
                    
                    // Here you would also log the maintenance request with notes and type
                    // For example:
                    // $this->maintenanceService->logMaintenanceRequest($bicycle, $maintenanceType, $notes);
                    
                    $success++;
                } else {
                    $failures++;
                }
            } catch (\Exception $e) {
                $this->logger->error('Error scheduling maintenance for bicycle #' . $id . ': ' . $e->getMessage());
                $failures++;
            }
        }
        
        if ($success > 0) {
            $this->addFlash('success', sprintf('%d bicycle(s) scheduled for maintenance successfully', $success));
        }
        
        if ($failures > 0) {
            $this->addFlash('error', sprintf('Failed to schedule maintenance for %d bicycle(s)', $failures));
        }
        
        return $this->redirectToRoute('admin_bicycle_dashboard', ['tab' => 'bicycles']);
    }

    // API Routes - Only keeping the one that's actively used
    
    #[Route('/api/locations', name: 'api_locations', methods: ['GET'])]
    public function getLocations(): JsonResponse
    {
        try {
            $locations = $this->locationService->getAllLocations();
            
            $data = [];
            foreach ($locations as $location) {
                $data[] = [
                    'id' => $location->getIdLocation(),
                    'address' => $location->getAddress(),
                    'latitude' => (float)$location->getLatitude(),
                    'longitude' => (float)$location->getLongitude()
                ];
            }
            
            return new JsonResponse($data);
        } catch (\Exception $e) {
            $this->logger->error('Error getting locations: ' . $e->getMessage());
            return new JsonResponse(['error' => 'Failed to retrieve locations'], 500);
        }
    }    #[Route('/bulk-assign-station', name: 'admin_bicycle_bulk_assign_station', methods: ['POST'])]
    public function bulkAssignBicyclesToStation(Request $request, ValidatorInterface $validator): JsonResponse
    {
        $response = null;
        $data = json_decode($request->getContent(), true);
        
        // Handle invalid data format
        if (!$data || !isset($data['assignments']) || !is_array($data['assignments'])) {
            $response = new JsonResponse(['success' => false, 'message' => 'Invalid data format'], 400);
        } else {
            $assignments = $data['assignments'];
            $updatedCount = 0;
            $errors = [];
            
            $this->entityManager->beginTransaction();
            
            try {
                foreach ($assignments as $assignment) {
                    $bicycleId = $assignment['bicycleId'] ?? null;
                    $stationId = $assignment['stationId'] ?? null;
                    
                    if (!$bicycleId || !$stationId) {
                        $errors[] = 'Missing bicycle ID or station ID';
                        continue;
                    }
                    
                    $bicycle = $this->bicycleService->getBicycle((int)$bicycleId);
                    $station = $this->stationService->getStation((int)$stationId);
                    
                    if (!$bicycle) {
                        $errors[] = "Bicycle #$bicycleId not found";
                        continue;
                    }
                    
                    if (!$station) {
                        $errors[] = "Station #$stationId not found";
                        continue;
                    }
                    
                    // Check if station has available docks
                    if ($station->getAvailableDocks() <= 0) {
                        $errors[] = "Station {$station->getName()} has no available docks";
                        continue;
                    }
                    
                    // Update bicycle station
                    $bicycle->setBicycleStation($station);
                    
                    // Validate
                    $validationErrors = $validator->validate($bicycle);
                    if (count($validationErrors) > 0) {
                        $errorMessages = [];
                        foreach ($validationErrors as $error) {
                            $errorMessages[] = $error->getMessage();
                        }
                        $errors[] = "Validation failed for bicycle #$bicycleId: " . implode(', ', $errorMessages);
                        continue;
                    }
                    
                    
                    $station->setAvailableDocks($station->getAvailableDocks() - 1);
                    $station->setAvailableBikes($station->getAvailableBikes() + 1);
                    
                    
                    $updatedCount++;
                }
                
                if ($updatedCount > 0) {
                    $this->entityManager->flush();
                    $this->entityManager->commit();
                    
                    $response = new JsonResponse([
                        'success' => true, 
                        'updatedCount' => $updatedCount,
                        'errors' => $errors
                    ]);
                } else {
                    $this->entityManager->rollback();
                    
                    $response = new JsonResponse([
                        'success' => false,
                        'message' => 'No bicycles were assigned. ' . implode('; ', $errors)
                    ], 400);
                }
            } catch (\Exception $e) {
                $this->entityManager->rollback();
                $this->logger->error('Error assigning bicycles to station: ' . $e->getMessage());
                
                $response = new JsonResponse([
                    'success' => false,
                    'message' => 'Error: ' . $e->getMessage()
                ], 500);
            }
        }
        
        return $response;
    }

    /**
     * Helper function to get bicycle status choices
     */
    private function getStatusChoices(): array
    {
        $choices = [];
        foreach (BICYCLE_STATUS::cases() as $status) {
            $label = ucfirst(strtolower(str_replace('_', ' ', $status->name)));
            $choices[$label] = $status->value;
        }
        return $choices;
    }
    #[Route('/{id}/details', name: 'admin_bicycle_details', methods: ['GET'])]
    public function details(
        int $id, 
        EntityManagerInterface $em
    ): Response {
        $bicycle = $em->getRepository(Bicycle::class)->find($id);
        
        if (!$bicycle) {
            $this->addFlash('error', 'Bicycle not found.');
            return $this->redirectToRoute('admin_bicycle_rentals', ['tab' => 'bicycles']);
        }
        
        // Get rental history for this bicycle
        $rentalHistory = $em->getRepository(BicycleRental::class)->findBy(
            ['bicycle' => $bicycle],
            ['start_time' => 'DESC'],
            10 // Limit to last 10 rentals
        );
        
        // Get statistics
        $stats = [
            'totalRentals' => count($rentalHistory),
            'totalDistance' => 0,
            'totalRevenue' => 0,
            'totalRentalDuration' => 0,
            'avgRentalDuration' => 0,
        ];
        
        $totalDuration = 0;
        foreach ($rentalHistory as $rental) {
            $stats['totalDistance'] += $rental->getDistance_km() ?? 0;
            $stats['totalRevenue'] += $rental->getCost() ?? 0;
            
            if ($rental->getEnd_time() && $rental->getStart_time()) {
                $duration = $rental->getEnd_time()->getTimestamp() - $rental->getStart_time()->getTimestamp();
                $totalDuration += $duration;
            }
        }
        
        $stats['totalRentalDuration'] = $totalDuration;
        
        if ($stats['totalRentals'] > 0) {
            $stats['avgRentalDuration'] = round($totalDuration / $stats['totalRentals'] / 60); // in minutes
        }
        
        // Get all stations for map display
        $stations = $em->getRepository(BicycleStation::class)->findAll();
        
        // Get maintenance history (placeholder)
        $maintenanceHistory = [];
        
        return $this->render('back-office/bicycle/Bicycle/bicycle-details.html.twig', [
            'bicycle' => $bicycle,
            'rentalHistory' => $rentalHistory,
            'statistics' => $stats,
            'stations' => $stations,
            'maintenanceHistory' => $maintenanceHistory
        ]);
    }

    /**
     * Export bicycles data in various formats
     */
    #[Route('/export', name: 'admin_bicycle_export', methods: ['GET'])]
    public function export(Request $request): Response
    {
        // Get filter parameters
        $status = $request->query->get('status');
        $stationId = $request->query->get('station');
        $format = $request->query->get('format', 'csv');
        
        // Create query builder with filters
        $queryBuilder = $this->entityManager->getRepository(Bicycle::class)
            ->createQueryBuilder('b')
            ->leftJoin('b.bicycleStation', 's')
            ->orderBy('b.idBike', 'ASC');
        
        // Apply filters
        if ($status) {
            $queryBuilder->andWhere('b.status = :status')
                ->setParameter('status', BICYCLE_STATUS::from($status));
        }
        
        if ($stationId) {
            $queryBuilder->andWhere('s.idStation = :stationId')
                ->setParameter('stationId', $stationId);
        }
        
        // Get all bicycles matching the criteria
        $bicycles = $queryBuilder->getQuery()->getResult();
        
        // Calculate statistics for PDF export
        $stats = [
            'totalBicycles' => count($bicycles),
            'availableCount' => 0,
            'inUseCount' => 0,
            'maintenanceCount' => 0,
            'chargingCount' => 0
        ];
        
        $batteryDistribution = [
            'premium' => 0,  // 90-100%
            'good' => 0,     // 60-89%
            'medium' => 0,   // 30-59%
            'low' => 0       // 0-29%
        ];
        
        // Set up data for export
        $headers = [
            'ID', 'Status', 'Battery Level (%)', 'Range (km)', 'Station', 'Last Updated'
        ];
        
        $exportData = [];
        
        foreach ($bicycles as $bicycle) {
            // Update statistics counts
            $status = $bicycle->getStatus()->value;
            switch($status) {
                case 'available':
                    $stats['availableCount']++;
                    break;
                case 'in_use':
                    $stats['inUseCount']++;
                    break;
                case 'maintenance':
                    $stats['maintenanceCount']++;
                    break;
                case 'charging':
                    $stats['chargingCount']++;
                    break;
            }
            
            // Update battery distribution
            $batteryLevel = $bicycle->getBatteryLevel();
            if ($batteryLevel >= 90) {
                $batteryDistribution['premium']++;
            } elseif ($batteryLevel >= 60) {
                $batteryDistribution['good']++;
            } elseif ($batteryLevel >= 30) {
                $batteryDistribution['medium']++;
            } else {
                $batteryDistribution['low']++;
            }
            
            // Format status for display
            $statusLabel = ucfirst(str_replace('_', ' ', $status));
            
            // Add row to export data
            $exportData[] = [
                $bicycle->getIdBike(),
                $statusLabel,
                $bicycle->getBatteryLevel(),
                $bicycle->getRangeKm(),
                $bicycle->getBicycleStation() ? $bicycle->getBicycleStation()->getName() : '-',
                $bicycle->getLastUpdated()->format('Y-m-d H:i')
            ];
        }
        
        // Set filters context for PDF export
        $filters = [
            'status' => $status ? ucfirst($status) : '',
            'stationId' => $stationId
        ];
        
        // Export based on requested format
        switch ($format) {
            case 'excel':
                $columnStyles = [
                    2 => ['format' => NumberFormat::FORMAT_PERCENTAGE_00], // Battery Level column
                    3 => ['format' => NumberFormat::FORMAT_NUMBER_00],     // Range column
                ];
                
                return $this->exportService->exportToExcel(
                    $headers, 
                    $exportData, 
                    'bicycles-export', 
                    $columnStyles,
                    'Bicycle Inventory'
                );
                
            case 'pdf':
                return $this->exportService->exportToPdf(
                    'back-office/export/bicycles-pdf.html.twig',
                    [
                        'bicycles' => $bicycles,
                        'stats' => $stats,
                        'filters' => $filters,
                        'batteryDistribution' => $batteryDistribution,
                        'title' => 'Bicycle Inventory Export'
                    ],
                    'bicycles-export'
                );
                
            case 'csv':
            default:
                return $this->exportService->exportToCsv(
                    $headers,
                    $exportData,
                    'bicycles-export'
                );
        }
    }
    
    /**
     * Recursively extracts form errors
     */
    private function getFormErrors($form): array
    {
        $errors = [];

        foreach ($form->getErrors() as $error) {
            $errors[] = $error->getMessage();
        }

        foreach ($form->all() as $childForm) {
            if ($childForm instanceof \Symfony\Component\Form\FormInterface) {
                $childErrors = $this->getFormErrors($childForm);
                if ($childErrors) {
                    $errors[$childForm->getName()] = $childErrors;
                }
            }
        }

        return $errors;
    }
}