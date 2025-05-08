<?php

namespace App\Controller\front\bicycle;

use App\Entity\Bicycle;
use App\Entity\BicycleStation;
use App\Entity\BicycleRental;
use App\Entity\User;
use App\Enum\BICYCLE_STATUS;
use App\Service\BicycleService;
use App\Service\BicycleStationService;
use App\Service\BicycleRentalService;
use App\Service\GeminiApiService;
use App\Service\Geo\GeoRoutingService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Constraints as Assert;
use App\Repository\BicycleStationRepository;
use Psr\Log\LoggerInterface;

#[Route('/services/bicycle')]
class BicycleRentalsController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly BicycleService $bicycleService,
        private readonly BicycleStationService $stationService,
        private readonly BicycleRentalService $rentalService,
        private readonly Security $security,
        private readonly ValidatorInterface $validator,
        private readonly GeminiApiService $geminiApiService,
        private readonly GeoRoutingService $geoRoutingService,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Main bicycle rentals page
     */
    #[Route('/', name: 'app_front_services_bicycle')]
    public function index(): Response
    {
        // Refresh available bikes counts to ensure accuracy
        $this->stationService->refreshAvailableBikesCounts();

        // Get active stations for the map and listings
        $stations = $this->stationService->getAllActiveStations();

        // Get user's active rentals
        $activeRentals = [];
        $user = $this->security->getUser();
        if ($user) {
            $activeRentals = $this->rentalService->getActiveRentalsForUser($user);
        }

        return $this->render('front/bicycle/bicycle-rental.html.twig', [
            'stations' => $stations,
            'activeRentals' => $activeRentals
        ]);
    }

    /**
     * View station details with available bicycles
     */
    #[Route('/station/{id}', name: 'app_front_services_bicycle_station', requirements: ['id' => '\d+'])]
    public function stationDetails(int $id): Response
    {
        $station = $this->stationService->getStation($id);

        if (!$station) {
            throw $this->createNotFoundException('Station not found');
        }

        // Get available bicycles at this station
        $bicycles = $this->bicycleService->getBicyclesByStation($station, true);

        return $this->render('front/bicycle/station.html.twig', [
            'station' => $station,
            'bicycles' => $bicycles
        ]);
    }

    /**
     * View bicycle details
     */
    #[Route('/bicycle/{id}', name: 'app_front_services_bicycle_details', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function bicycleDetails(int $id): Response
    {
        $bicycle = $this->bicycleService->getBicycle($id);

        if (!$bicycle) {
            throw $this->createNotFoundException('Bicycle not found');
        }

        $bicycleInfo = $this->getBicycleDisplayInfo($bicycle);

        return $this->render('front/bicycle/bicycle-details.html.twig', [
            'bicycle' => $bicycle,
            'bicycleType' => $bicycleInfo['type'],
            'hourlyRate' => $bicycleInfo['hourlyRate']
        ]);
    }

    /**
     * Reserve a bicycle
     */
    #[Route('/bicycle/{id}/reserve', name: 'app_front_reserve_bicycle', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function reserveBicycle(Request $request, int $id): Response
    {
        $bicycle = $this->bicycleService->getBicycle($id);

        if (!$bicycle) {
            throw $this->createNotFoundException('Bicycle not found');
        }

        if ($bicycle->getStatus() !== BICYCLE_STATUS::AVAILABLE) {
            $this->addFlash('error', 'This bicycle is not available for reservation');
            return $this->redirectToRoute('app_front_services_bicycle_station', ['id' => $bicycle->getBicycleStation()->getIdStation()]);
        }

        $bicycleInfo = $this->getBicycleDisplayInfo($bicycle);
        
        // Get all active stations for destination selection
        $stations = $this->stationService->getAllActiveStations();

        // Process form submission
        if ($request->isMethod('POST')) {
            $duration = (int) $request->request->get('duration');
            $estimatedCost = (float) $request->request->get('estimatedCost');

            // Validate input
            $validationResult = $this->validateReservationInput([
                'duration' => $duration,
                'estimatedCost' => $estimatedCost
            ]);

            if (!$validationResult['isValid']) {
                foreach ($validationResult['errors'] as $error) {
                    $this->addFlash('error', $error);
                }
            } else {
                try {
                    $user = $this->security->getUser();
                    if (!$user) {
                        throw $this->createAccessDeniedException('You must be logged in to reserve a bicycle');
                    }
                
                    // Create reservation
                    $rental = $this->rentalService->reserveBicycle(
                        $user,
                        $bicycle,
                        $estimatedCost
                    );

                    return $this->redirectToRoute('app_front_rental_confirmation', [
                        'id' => $rental->getIdUserRental()
                    ]);
                } catch (\Exception $e) {
                    $this->addFlash('error', $e->getMessage());
                }
            }
        }

        return $this->render('front/bicycle/reserve.html.twig', [
            'bicycle' => $bicycle,
            'bicycleType' => $bicycleInfo['type'],
            'hourlyRate' => $bicycleInfo['hourlyRate'],
            'stations' => $stations // Added stations for destination dropdown
        ]);
    }

    /**
     * Rental confirmation page
     */
    #[Route('/rental/{id}/confirmation', name: 'app_front_rental_confirmation', requirements: ['id' => '\d+'])]
    public function rentalConfirmation(int $id): Response
    {
        $rental = $this->entityManager->getRepository(BicycleRental::class)->find($id);
        $user = $this->security->getUser();

        if (!$rental || !$user || $rental->getUser() !== $user) {
            throw $this->createNotFoundException('Rental not found');
        }

        return $this->render('front/bicycle/confirmation.html.twig', [
            'rental' => $rental,
            'reservationCode' => $this->generateReservationCode($rental)
        ]);
    }

    /**
     * Show rental code for pickup
     */
    #[Route('/rental/{id}/code', name: 'app_front_show_rental_code', requirements: ['id' => '\d+'])]
    public function showRentalCode(int $id): Response
    {
        $rental = $this->entityManager->getRepository(BicycleRental::class)->find($id);
        $user = $this->security->getUser();

        if (!$rental || !$user || $rental->getUser() !== $user) {
            throw $this->createNotFoundException('Rental not found');
        }

        return $this->render('front/bicycle/rental-code.html.twig', [
            'rental' => $rental,
            'reservationCode' => $this->generateReservationCode($rental)
        ]);
    }

    /**
     * Cancel a rental
     */
    #[Route('/rental/{id}/cancel', name: 'app_front_cancel_rental', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function cancelRental(Request $request, int $id): Response
    {
        $rental = $this->entityManager->getRepository(BicycleRental::class)->find($id);
        $user = $this->security->getUser();

        if (!$rental || !$user || $rental->getUser() !== $user) {
            throw $this->createNotFoundException('Rental not found');
        }

        if ($request->isMethod('POST')) {
            $this->rentalService->cancelRental($rental);
            $this->addFlash('success', 'Your reservation has been cancelled');
            return $this->redirectToRoute('app_front_my_reservations');
        }

        return $this->render('front/bicycle/cancel-rental.html.twig', [
            'rental' => $rental
        ]);
    }

    /**
     * View user's reservations
     */
    #[Route('/my-reservations', name: 'app_front_my_reservations')]
    public function myReservations(): Response
    {
        $user = $this->security->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException('You must be logged in to view your reservations');
        }
        
        $activeRentals = $this->rentalService->getActiveRentalsForUser($user);
        $pastRentals = $this->rentalService->getPastRentalsForUser($user);

        return $this->render('front/bicycle/my-reservations.html.twig', [
            'activeRentals' => $activeRentals,
            'pastRentals' => $pastRentals
        ]);
    }

    /**
     * API: Get all active stations
     */
    #[Route('/stations', name: 'app_api_bicycle_stations', methods: ['GET'])]
    public function getStations(): JsonResponse
    {
        $stations = $this->stationService->getAllActiveStations();
        $stationsData = [];

        foreach ($stations as $station) {
            $stationsData[] = [
                'id' => $station->getIdStation(),
                'name' => $station->getName(),
                'location' => $station->getLocation() ? [
                    'lat' => $station->getLocation()->getLatitude(),
                    'lng' => $station->getLocation()->getLongitude(),
                    'address' => $station->getLocation()->getAddress()
                ] : null,
                'availableBikes' => $station->getAvailableBikes(),
                'availableDocks' => $station->getAvailableDocks(),
                'chargingBikes' => $station->getChargingBikes(),
                'totalDocks' => $station->getTotalDocks(),
                'status' => $station->getStatus()->value
            ];
        }

        return new JsonResponse($stationsData);
    }

    /**
     * API: Get bicycles at a station
     */
    #[Route('/station/{id}/bicycles', name: 'app_api_station_bicycles', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function getStationBicycles(int $id): JsonResponse
    {
        $station = $this->stationService->getStation($id);

        if (!$station) {
            return new JsonResponse(['error' => 'Station not found'], Response::HTTP_NOT_FOUND);
        }

        $bicycles = $this->bicycleService->getBicyclesByStation($station, true);
        $bicycleData = [];

        foreach ($bicycles as $bicycle) {
            $bicycleInfo = $this->getBicycleDisplayInfo($bicycle);
            $bicycleData[] = [
                'id' => $bicycle->getIdBike(),
                'batteryLevel' => $bicycle->getBatteryLevel(),
                'rangeKm' => $bicycle->getRangeKm(),
                'type' => $bicycleInfo['type'],
                'lastUpdated' => $bicycle->getLastUpdated()->format('Y-m-d H:i:s'),
                'status' => $bicycle->getStatus()->value,
                'hourlyRate' => $bicycleInfo['hourlyRate']
            ];
        }

        return new JsonResponse([
            'station' => [
                'id' => $station->getIdStation(),
                'name' => $station->getName()
            ],
            'bicycles' => $bicycleData
        ]);
    }

    /**
     * API: Get bicycle details
     */
    #[Route('/bicycle/{id}', name: 'app_api_bicycle_detail', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function getBicycleDetails(int $id): JsonResponse
    {
        $bicycle = $this->bicycleService->getBicycle($id);

        if (!$bicycle) {
            return new JsonResponse(['error' => 'Bicycle not found'], Response::HTTP_NOT_FOUND);
        }

        $bicycleInfo = $this->getBicycleDisplayInfo($bicycle);

        return new JsonResponse([
            'id' => $bicycle->getIdBike(),
            'type' => $bicycleInfo['type'],
            'batteryLevel' => $bicycle->getBatteryLevel(),
            'rangeKm' => $bicycle->getRangeKm(),
            'lastUpdated' => $bicycle->getLastUpdated()->format('Y-m-d H:i:s'),
            'station' => [
                'id' => $bicycle->getBicycleStation()->getIdStation(),
                'name' => $bicycle->getBicycleStation()->getName(),
                'location' => $bicycle->getBicycleStation()->getLocation() ?
                    $bicycle->getBicycleStation()->getLocation()->getAddress() : null
            ],
            'hourlyRate' => $bicycleInfo['hourlyRate']
        ]);
    }

    /**
     * API: Reserve a bicycle
     */
    #[Route('/reserve/{id}', name: 'app_api_reserve_bicycle', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function apiReserveBicycle(int $id, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        // Validate input data
        $validationResult = $this->validateReservationInput($data);
        if (!$validationResult['isValid']) {
            return new JsonResponse(['error' => $validationResult['errors']], Response::HTTP_BAD_REQUEST);
        }

        $bicycle = $this->bicycleService->getBicycle($id);

        if (!$bicycle) {
            return new JsonResponse(['error' => 'Bicycle not found'], Response::HTTP_NOT_FOUND);
        }

        if ($bicycle->getStatus() !== BICYCLE_STATUS::AVAILABLE) {
            return new JsonResponse(['error' => 'This bicycle is not available for reservation'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $user = $this->entityManager->getRepository(User::class)->find(1);
            $rental = $this->rentalService->reserveBicycle(
                $user,
                $bicycle,
                $data['estimatedCost']
            );

            return new JsonResponse([
                'success' => true,
                'rentalId' => $rental->getIdUserRental(),
                'reservationCode' => $this->generateReservationCode($rental),
                'message' => 'Bicycle reserved successfully!'
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * API: Cancel a rental
     */
    #[Route('/api/rental/{id}/cancel', name: 'app_api_cancel_rental', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function apiCancelRental(int $id): JsonResponse
    {
        $rental = $this->entityManager->getRepository(BicycleRental::class)->find($id);
        $user = $this->entityManager->getRepository(User::class)->find(1);

        if (!$rental) {
            return new JsonResponse(['error' => 'Rental not found'], Response::HTTP_NOT_FOUND);
        }

        if ($rental->getUser() !== $user) {
            return new JsonResponse(['error' => 'You can only cancel your own reservations'], Response::HTTP_FORBIDDEN);
        }

        try {
            $this->rentalService->cancelRental($rental);
            return new JsonResponse([
                'success' => true,
                'message' => 'Rental cancelled successfully'
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * API: Get AI-powered rental prediction
     */
    #[Route('/api-rental-predict', name: 'app_api_rental_predict', methods: ['POST'], options: ['expose' => true])]
    public function getRentalPrediction(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        

        if (!isset($data['startStationId']) || !isset($data['endStationId']) || !isset($data['bicycleId'])) {
            return new JsonResponse(['error' => 'Missing required parameters'], Response::HTTP_BAD_REQUEST);
        }

        $startStation = $this->entityManager->getRepository(BicycleStation::class)->find($data['startStationId']);
        $endStation = $this->entityManager->getRepository(BicycleStation::class)->find($data['endStationId']);
        $bicycle = $this->entityManager->getRepository(Bicycle::class)->find($data['bicycleId']);
        

        if (!$startStation || !$endStation || !$bicycle) {
            return new JsonResponse(['error' => 'One or more requested resources not found'], Response::HTTP_NOT_FOUND);
        }
        
        try {
            $bicycleInfo = $this->getBicycleDisplayInfo($bicycle);
            $hourlyRate = $bicycleInfo['hourlyRate'];
            
            $startLat = $startStation->getLocation() ? $startStation->getLocation()->getLatitude() : 0;
            $startLon = $startStation->getLocation() ? $startStation->getLocation()->getLongitude() : 0;
            $endLat = $endStation->getLocation() ? $endStation->getLocation()->getLatitude() : 0;
            $endLon = $endStation->getLocation() ? $endStation->getLocation()->getLongitude() : 0;
            

            $routeData = $this->geoRoutingService->calculateRouteDistance(
                $startLat,
                $startLon,
                $endLat,
                $endLon
            );
            

            $distance = $routeData['distance'];
            

            $elevationData = $this->geoRoutingService->getElevationData(
                $startLat,
                $startLon,
                $endLat,
                $endLon
            );
            

            $terrainType = $elevationData['route_type'];
            
            $this->logger->info('Starting AI prediction with enhanced geo data', [
                'from' => $startStation->getName(),
                'to' => $endStation->getName(),
                'distance' => $distance,
                'routing_provider' => $routeData['provider'],
                'terrain' => $terrainType,
                'bicycle_id' => $bicycle->getIdBike()
            ]);
            

            $prediction = $this->getPredictionFromAI(
                $startStation,
                $endStation,
                $bicycle,
                $distance,
                $hourlyRate,
                $routeData,
                $elevationData
            );
            

            if (isset($prediction['is_default']) && $prediction['is_default']) {
                $errorReason = $prediction['error_reason'] ?? 'Unknown error';
                $this->logger->warning('Using fallback prediction', ['reason' => $errorReason]);
                

                $prediction['_message'] = "We've provided an estimated prediction as we couldn't generate a precise one. Reason: {$errorReason}";
            }

            try {
                $midpointLat = ($startLat + $endLat) / 2;
                $midpointLon = ($startLon + $endLon) / 2;
                

                $searchRadius = max(0.5, min(2, $distance / 3));
                
   
                $pois = $this->geoRoutingService->findNearbyPointsOfInterest(
                    $midpointLat, 
                    $midpointLon,
                    $searchRadius
                );
                
           
                if (!empty($pois)) {
                    $poiNames = [];
                    foreach (array_slice($pois, 0, 3) as $poi) {
                        $poiNames[] = $poi['name'];
                    }
                    $prediction['pointsOfInterest'] = implode(', ', $poiNames);
                }
            } catch (\Exception $e) {
                $this->logger->warning('Failed to fetch POIs', ['error' => $e->getMessage()]);
           
            }
            
            return new JsonResponse($prediction);
            
        } catch (\Exception $e) {
            $this->logger->error('Exception in rental prediction', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $errorMessage = 'Unable to generate prediction. ';
            

            if (strpos($e->getMessage(), 'API') !== false) {
                $errorMessage .= 'Our prediction service is temporarily unavailable.';
            } elseif (strpos($e->getMessage(), 'JSON') !== false) {
                $errorMessage .= 'There was an issue processing the prediction data.';
            } else {
                $errorMessage .= 'Please try again later.';
            }
            
            return new JsonResponse([
                'error' => $errorMessage,
                'technicalDetails' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }   
    
    /**
     * Generate prediction using AI (Gemini API)
     */
    private function getPredictionFromAI(
        BicycleStation $startStation,
        BicycleStation $endStation,
        Bicycle $bicycle,
        float $distance,
        float $hourlyRate,
        array $routeData = [],
        array $elevationData = []
    ): array {
        $weatherInfo = $this->getWeatherInfo($startStation);
        
        $prompt = $this->createAIPrompt(
            $startStation,
            $endStation,
            $bicycle,
            $distance,
            $hourlyRate,
            $weatherInfo,
            $routeData,
            $elevationData
        );
        
        $response = $this->geminiApiService->generateContent($prompt);
        
        if (isset($response['error'])) {
            throw new \Exception('AI Prediction Error: ' . $response['error']);
        }
        
        $prediction = $this->parseGeminiResponse($response);
        
        if (empty($prediction) || !isset($prediction['estimatedDuration'])) {
            return $this->getFallbackPrediction($distance, $bicycle, $hourlyRate, $routeData);
        }
        
        return $prediction;
    }
    
    /**
     * Create the prompt for the Gemini API
     */
    private function createAIPrompt(
        BicycleStation $startStation,
        BicycleStation $endStation,
        Bicycle $bicycle,
        float $distance,
        float $hourlyRate,
        string $weatherInfo,
        array $routeData = [],
        array $elevationData = []
    ): string {
        // Get location coordinates for more accurate calculations
        $startLat = $startStation->getLocation() ? $startStation->getLocation()->getLatitude() : 0;
        $startLon = $startStation->getLocation() ? $startStation->getLocation()->getLongitude() : 0;
        $endLat = $endStation->getLocation() ? $endStation->getLocation()->getLatitude() : 0;
        $endLon = $endStation->getLocation() ? $endStation->getLocation()->getLongitude() : 0;
        
        // Add enhanced routing data if available
        $routeType = $routeData['route_type'] ?? 'cycling';
        $routeProvider = $routeData['provider'] ?? 'estimated';
        $estimatedDuration = $routeData['duration'] ?? 'calculating';
        
        // Add terrain details from elevation data
        $terrainInfo = "";
        if (!empty($elevationData) && isset($elevationData['route_type']) && $elevationData['route_type'] !== 'unknown') {
            $elevDiff = abs($elevationData['elevation_difference']);
            $terrainInfo = "Terrain is primarily {$elevationData['route_type']} with an elevation change of {$elevDiff} meters.";
        }
        
        return <<<EOT
Act as a bicycle rental prediction system for an electric bicycle service.
Please predict and analyze a rental trip with these accurate details:

Starting point: {$startStation->getName()} (coordinates: {$startLat}, {$startLon})
Destination: {$endStation->getName()} (coordinates: {$endLat}, {$endLon})
Calculated route distance: {$distance} km (using {$routeProvider} routing)
Estimated cycling time: approximately {$estimatedDuration} minutes
{$terrainInfo}
Electric bicycle battery level: {$bicycle->getBatteryLevel()}%
Current range: {$bicycle->getRangeKm()} km
Weather conditions: {$weatherInfo}
Hourly rental rate: {$hourlyRate} TND

Important information:
- Batteries can be recharged at any bicycle station if needed during the trip.
- The average cycling speed is about 15km/h in good conditions.
- Battery consumption is roughly 5-8% per kilometer.
- Factor in traffic, terrain, and weather conditions for a realistic price estimate.

Provide a comprehensive analysis including all of the following (leave no field empty):
1. Refined distance estimate in kilometers based on the routing data provided
2. Estimated duration in minutes considering traffic, terrain, and weather
3. Estimated cost based on the hourly rate and predicted duration
4. Detailed weather impact analysis (how specific weather conditions will affect the ride)
5. Expected battery consumption percentage for this trip
6. Remaining range in km after the trip
7. Route recommendation including at least one notable landmark or area to pass through
8. Safety tips specific to this route and current conditions
9. Health benefits estimate (calories burned, exercise intensity)
10. If battery might be insufficient, suggest recharging options at stations along the route
11. Points of interest along the route that the rider might want to visit
12. Traffic conditions that might affect the journey
13. Potential rest stops or cafés along the way
14. Terrain description (flat, hilly, etc.) and how it affects the journey
15. Expected environmental impact (CO2 emissions saved compared to car travel)

Return your analysis in this exact JSON format with values for ALL fields (no empty or placeholder values):
{
  "distance": [calculated distance in km, numeric],
  "estimatedDuration": [minutes in integer],
  "estimatedCost": [cost in TND with 3 decimal places],
  "weatherImpact": [detailed description of how weather affects the trip],
  "batteryConsumption": [estimated percentage as integer],
  "rangeAfterTrip": [estimated remaining range in km],
  "routeSuggestion": [brief description of recommended route with at least one landmark],
  "safetyTips": [one or two safety recommendations specific to conditions],
  "healthBenefits": [brief description of health benefits with estimated calories],
  "difficultyLevel": [one of: "Easy", "Moderate", "Challenging"],
  "rechargingNeeded": [boolean true/false indicating if recharging is recommended],
  "rechargingSuggestion": [recharging recommendation if needed, otherwise "No recharging needed"],
  "pointsOfInterest": [1-3 interesting places along the route],
  "trafficConditions": [description of expected traffic conditions],
  "restStops": [suggested rest stops or cafés if applicable],
  "terrainDescription": [description of the terrain and its impact],
  "environmentalImpact": [CO2 savings compared to car travel]
}
EOT;
    }

    /**
     * Create a default prediction when JSON parsing fails
     */
    private function createDefaultPrediction(string $reason, array $routeData = []): array
    {
        $this->logger->warning('Using default prediction', ['reason' => $reason]);
        
        $defaultDistance = $routeData['distance'] ?? 3.0;
        
        $duration = $routeData['duration'] ?? $this->geoRoutingService->estimateCyclingDuration($defaultDistance);
        $cost = $this->parseCostFromResponse([], $duration);
        $batteryConsumption = $this->parseBatteryConsumptionFromResponse([], $defaultDistance);
        $rangeAfterTrip = $this->parseRangeAfterTripFromResponse([], $defaultDistance);
        
        return [
            'distance' => $defaultDistance,
            'estimatedDuration' => $duration,
            'estimatedCost' => $cost,
            'weatherImpact' => 'Weather impact data not available.',
            'batteryConsumption' => $batteryConsumption,
            'rangeAfterTrip' => $rangeAfterTrip,
            'routeSuggestion' => 'Take the most direct route between stations.',
            'safetyTips' => 'Follow standard cycling safety practices.',
            'healthBenefits' => 'About ' . $this->calculateCalories($defaultDistance) . ' calories burned.',
            'difficultyLevel' => $this->calculateDifficultyLevel($defaultDistance),
            'rechargingNeeded' => $batteryConsumption > 75,
            'rechargingSuggestion' => $batteryConsumption > 75 ? 
                'Consider recharging during your journey.' : 
                'No recharging needed for this trip.',
            'is_default' => true,
            'error_reason' => $reason
        ];
    }
    
    /**
     * Parse distance from API response with sanity checks
     */
    private function parseDistanceFromResponse(array $data, string $text, array $routeData = []): float
    {
        // If we have route data from our geo service, use that as the primary source
        if (!empty($routeData) && isset($routeData['distance'])) {
            return (float) $routeData['distance'];
        }
        
        // First try to get a valid distance from the parsed JSON
        if (isset($data['distance']) && is_numeric($data['distance'])) {
            $distance = (float) $data['distance'];
            if ($distance >= 0.5) {
                return $distance; // Return if it's a reasonable value
            }
        }
        
        // If that fails, try to extract from the text
        if (preg_match('/distance.*?(\d+\.?\d*)\s*km/i', $text, $matches)) {
            $distance = (float) $matches[1];
            if ($distance >= 0.5) {
                return $distance;
            }
        }
        
        // If we get here, use a default
        return 3.0; // More realistic default than 2.0
    }

    /**
     * Parse duration with sanity checks
     */
    private function parseDurationFromResponse(array $data, float $distance): int
    {
        // Try to use AI's duration if available and reasonable
        if (isset($data['estimatedDuration']) && is_numeric($data['estimatedDuration'])) {
            $duration = (int) $data['estimatedDuration'];
            
            // Sanity check: Duration should be between 5 min and 2 hours
            if ($duration >= 5 && $duration <= 120) {
                // Additional sanity check: average speed should be between 5 and 25 km/h
                $speedKmPerHour = ($distance / $duration) * 60;
                if ($speedKmPerHour >= 5 && $speedKmPerHour <= 25) {
                    return $duration;
                }
            }
        }
        
        // Calculate a realistic duration based on distance
        // Average e-bike speed of 15km/h (0.25 km/min) for moderate distances
        // Shorter trips are slower due to starting/stopping
        if ($distance < 2) {
            return max(8, round($distance / 0.15)); // Slower for very short trips
        } else if ($distance < 5) {
            return max(10, round($distance / 0.2)); // Medium speed for short trips
        } else {
            return max(15, round($distance / 0.25)); // Full speed for longer trips
        }
    }
    
    /**
     * Parse cost with sanity checks
     */
    private function parseCostFromResponse(array $data, int $durationMinutes): float
    {
        // Try to use AI's cost if available and reasonable
        if (isset($data['estimatedCost']) && is_numeric($data['estimatedCost'])) {
            $cost = (float) $data['estimatedCost'];
            
            // Basic sanity check
            $hourlyRate = 4.25; // Standard hourly rate
            $expectedCost = ($durationMinutes / 60) * $hourlyRate;
            
            // If AI's cost is within 30% of calculated cost, use it
            if ($cost >= ($expectedCost * 0.7) && $cost <= ($expectedCost * 1.3)) {
                return round($cost, 3);
            }
        }
        
        // Calculate cost based on duration and standard hourly rate
        $hourlyRate = 4.25;
        return round(($durationMinutes / 60) * $hourlyRate, 3);
    }
    
    /**
     * Parse battery consumption with sanity checks
     */
    private function parseBatteryConsumptionFromResponse(array $data, float $distance): int
    {
        // Try to use AI's battery consumption if available and reasonable
        if (isset($data['batteryConsumption']) && is_numeric($data['batteryConsumption'])) {
            $consumption = (int) $data['batteryConsumption'];
            
            // Sanity check: consumption should be proportional to distance
            $expectedConsumption = $distance * 6.5; // About 6.5% per km
            // If AI's consumption is within 30% of calculated consumption, use it
            if ($consumption >= max(5, $expectedConsumption * 0.7) && 
                $consumption <= min(95, $expectedConsumption * 1.3)) {
                return $consumption;
            }
        }
        
        // Calculate battery consumption based on distance with some randomness
        $baseConsumption = $distance * (mt_rand(55, 75) / 10); // 5.5-7.5% per km with some variation
        return min(95, max(5, round($baseConsumption)));
    }
    
    /**
     * Parse remaining range with sanity checks
     */
    private function parseRangeAfterTripFromResponse(array $data, float $distance): float
    {
        if (isset($data['rangeAfterTrip']) && is_numeric($data['rangeAfterTrip'])) {
            $range = (float) $data['rangeAfterTrip'];
            
            $fullRange = 30.0;
            $expectedRange = max(0, $fullRange - $distance);
            
            if ($range >= ($expectedRange * 0.7) && $range <= ($expectedRange * 1.3)) {
                return round($range, 1);
            }
        }
        
        $fullRange = mt_rand(28, 32);
        $remainingRange = max(0, $fullRange - $distance);
        return round($remainingRange, 1);
    }
    
    /**
     * Calculate calories burned based on distance
     */
    private function calculateCalories(float $distance): string
    {
        $caloriesPerKm = mt_rand(30, 50);
        $totalCalories = round($distance * $caloriesPerKm);
        
        $lowerBound = round($totalCalories * 0.9);
        $upperBound = round($totalCalories * 1.1);
        
        return "{$lowerBound}-{$upperBound}";
    }
    
    /**
     * Calculate difficulty level based on distance
     */
    private function calculateDifficultyLevel(float $distance): string
    {
        if ($distance < 3) {
            return "Easy";
        } else if ($distance < 8) {
            return "Moderate";
        } else {
            return "Challenging";
        }
    }

    /**
     * Helper function to get display information for a bicycle
     */
    private function getBicycleDisplayInfo(Bicycle $bicycle): array
    {
        $isPremium = $bicycle->getBatteryLevel() > 90;
        
        return [
            'type' => $isPremium ? 'Premium E-Bike' : 'Standard E-Bike',
            'hourlyRate' => $isPremium ? 5.00 : 3.50
        ];
    }

    /**
     * Helper function to generate a reservation code
     */
    private function generateReservationCode(BicycleRental $rental): string
    {
        return 'B' . str_pad($rental->getIdUserRental(), 5, '0', STR_PAD_LEFT);
    }

    /**
     * Helper function to validate reservation input
     */
    private function validateReservationInput(array $data): array
    {
        $constraints = new Assert\Collection([
            'duration' => [
                new Assert\NotBlank(),
                new Assert\Type(['type' => 'numeric']),
                new Assert\Range(['min' => 1, 'max' => 24])
            ],
            'estimatedCost' => [
                new Assert\NotBlank(),
                new Assert\Type(['type' => 'numeric']),
                new Assert\Positive()
            ]
        ]);

        $errors = $this->validator->validate($data, $constraints);
        $errorMessages = [];
        
        if (count($errors) > 0) {
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            return ['isValid' => false, 'errors' => $errorMessages];
        }
        
        return ['isValid' => true, 'errors' => []];
    }
    
    /**
     * Validate a BicycleRental entity using Symfony's validator
     */
    private function validateBicycleRental(BicycleRental $rental): array
    {
        $errors = $this->validator->validate($rental);
        $errorMessages = [];
        
        if (count($errors) > 0) {
            foreach ($errors as $error) {
                $errorMessages[] = $error->getPropertyPath() . ': ' . $error->getMessage();
            }
            return ['isValid' => false, 'errors' => $errorMessages];
        }
        
        return ['isValid' => true, 'errors' => []];
    }

    /**
     * Normalize data from Gemini API
     */
    private function normalizeGeminiData(array $data): array
    {
        $normalized = [];
        
        foreach (['distance', 'estimatedDuration', 'estimatedCost', 'batteryConsumption', 'rangeAfterTrip'] as $key) {
            if (isset($data[$key])) {
                if (is_array($data[$key]) && count($data[$key]) > 0) {
                    $normalized[$key] = is_numeric($data[$key][0]) ? (float)$data[$key][0] : $data[$key][0];
                } else {
                    $normalized[$key] = $data[$key];
                }
            } else {
                $defaultValues = [
                    'distance' => 1.0,
                    'estimatedDuration' => 5,
                    'estimatedCost' => 0.5,
                    'batteryConsumption' => 10,
                    'rangeAfterTrip' => 45,
                ];
                $normalized[$key] = $defaultValues[$key];
            }
        }
        
        // Handle string fields, which might come as arrays or plain strings
        foreach (['weatherImpact', 'routeSuggestion', 'safetyTips', 'healthBenefits', 'difficultyLevel', 'rechargingSuggestion'] as $key) {
            if (isset($data[$key])) {
                if (is_string($data[$key])) {
                    $normalized[$key] = $data[$key];
                }
                else if (is_array($data[$key]) && count($data[$key]) > 0) {
                    $normalized[$key] = is_string($data[$key][0]) ? $data[$key][0] : (string)$data[$key][0];
                }
                else if (is_array($data[$key]) && isset($data[$key]['description'])) {
                    $normalized[$key] = $data[$key]['description'];
                }
            } else {
                $fallbacks = [
                    'weatherImpact' => 'Weather conditions are favorable for riding',
                    'routeSuggestion' => 'Follow the main roads between the stations',
                    'safetyTips' => 'Wear a helmet and follow traffic rules',
                    'healthBenefits' => 'This ride will provide good cardiovascular exercise',
                    'difficultyLevel' => 'Moderate',
                    'rechargingSuggestion' => 'No recharging needed for this short journey'
                ];
                $normalized[$key] = $fallbacks[$key];
            }
        }
        
        // Boolean handling for rechargingNeeded specifically
        if (isset($data['rechargingNeeded'])) {
            if (is_array($data['rechargingNeeded']) && count($data['rechargingNeeded']) > 0) {
                if (is_string($data['rechargingNeeded'][0])) {
                    $normalized['rechargingNeeded'] = strtolower($data['rechargingNeeded'][0]) === 'true';
                } else {
                    $normalized['rechargingNeeded'] = (bool)$data['rechargingNeeded'][0];
                }
            } else {
                $normalized['rechargingNeeded'] = (bool)$data['rechargingNeeded'];
            }
        } else {
            $normalized['rechargingNeeded'] = false;
        }
        
        foreach (['pointsOfInterest', 'trafficConditions', 'restStops', 'terrainDescription', 'environmentalImpact'] as $key) {
            if (isset($data[$key])) {
                if (is_string($data[$key])) {
                    $normalized[$key] = $data[$key];
                }
                else if (is_array($data[$key])) {
                    if (count($data[$key]) > 0 && is_string($data[$key][0])) {
                        $normalized[$key] = $data[$key][0];
                    }

                    else {
                        $normalized[$key] = is_array($data[$key]) ? implode(", ", array_filter($data[$key], 'is_string')) : (string)$data[$key];
                    }
                }
            } else {
                $fallbacks = [
                    'pointsOfInterest' => 'There are various points of interest along this route',
                    'trafficConditions' => 'Normal traffic conditions expected',
                    'restStops' => 'Several cafés and rest areas available along the route',
                    'terrainDescription' => 'Mixed terrain with some flat sections and gentle hills',
                    'environmentalImpact' => 'By choosing a bicycle instead of a car, you\'re reducing CO2 emissions'
                ];
                $normalized[$key] = $fallbacks[$key];
            }
        }
        
        $this->logger->info('Normalized Gemini data', [
            'before' => $data,
            'after' => $normalized
        ]);
        
        return $normalized;
    }
    
    /**
     * Parse and normalize the response from the Gemini API
     */
    private function parseGeminiResponse(array $response): array
    {
        $this->logger->info('Parsing Gemini response', [
            'has_content' => isset($response['candidates'][0]['content']),
        ]);
        
        // Check if we have content in the response
        if (!isset($response['candidates'][0]['content']['parts'][0]['text'])) {
            $this->logger->error('Invalid Gemini API response structure', [
                'response' => $response
            ]);
            return $this->createDefaultPrediction('Invalid API response structure');
        }
        
        $text = $response['candidates'][0]['content']['parts'][0]['text'];
        
        if (preg_match('/\{.*\}/s', $text, $matches)) {
            $jsonText = $matches[0];
            
            $jsonText = str_replace(["\n", "\r"], '', $jsonText);
            $jsonText = preg_replace('/,\s*\}/', '}', $jsonText);
            
            try {
                $data = json_decode($jsonText, true, 512, JSON_THROW_ON_ERROR);

                $this->logger->info('Successfully parsed Gemini JSON response');
                
                return $this->normalizeGeminiData($data);
                
            } catch (\JsonException $e) {
                $this->logger->error('JSON parsing error in Gemini response', [
                    'error' => $e->getMessage(),
                    'text' => $jsonText
                ]);
            }
        }
        
        $this->logger->warning('Falling back to text extraction for Gemini response');
        
        $routeData = [];
        return $this->getFallbackPrediction(
            $this->parseDistanceFromResponse([], $text, $routeData),
            null,
            0.0,
            $routeData
        );
    }
    
    /**
     * Create a fallback prediction when AI service fails to provide structured data
     */
    private function getFallbackPrediction(
        float $distance, 
        ?Bicycle $bicycle = null, 
        float $hourlyRate = 0.0,
        array $routeData = []
    ): array {
        $this->logger->info('Using fallback prediction', [
            'distance' => $distance,
            'has_bicycle' => $bicycle !== null
        ]);
        
        $duration = isset($routeData['duration']) ? $routeData['duration'] : 
            $this->geoRoutingService->estimateCyclingDuration($distance);
        
        $batteryLevel = $bicycle ? $bicycle->getBatteryLevel() : 80;
        $rangeKm = $bicycle ? $bicycle->getRangeKm() : 30;
        

        $batteryConsumption = min(90, max(1, round($distance * 6)));
        

        $remainingRange = max(0, $rangeKm - $distance);
        

        $cost = $hourlyRate > 0 ? round(($duration / 60) * $hourlyRate, 2) : round($distance * 1.5, 2);
        

        $rechargingNeeded = $batteryConsumption > $batteryLevel * 0.75;
        
        return [
            'distance' => $distance,
            'estimatedDuration' => $duration,
            'estimatedCost' => $cost,
            'weatherImpact' => 'Weather conditions are favorable for cycling.',
            'batteryConsumption' => $batteryConsumption,
            'rangeAfterTrip' => $remainingRange,
            'routeSuggestion' => 'Take the most direct route between stations.',
            'safetyTips' => 'Wear a helmet and follow traffic rules. Watch for pedestrians.',
            'healthBenefits' => 'This ride will burn approximately ' . $this->calculateCalories($distance) . ' calories.',
            'difficultyLevel' => $this->calculateDifficultyLevel($distance),
            'rechargingNeeded' => $rechargingNeeded,
            'rechargingSuggestion' => $rechargingNeeded ? 
                'Recharging is recommended during your trip due to the distance.' : 
                'No recharging needed for this trip.',
            'pointsOfInterest' => 'Explore the area around the station.',
            'trafficConditions' => 'Normal traffic conditions expected.',
            'restStops' => 'There are cafes and shops near the stations.',
            'terrainDescription' => 'The route has a typical urban terrain with some gentle slopes.',
            'environmentalImpact' => 'By choosing a bicycle, you\'re saving approximately ' . 
                round($distance * 0.2, 1) . ' kg of CO2 compared to car travel.',
            'is_fallback' => true
        ];
    }

    /**
     * Get the current weather information for a bicycle station

     */
    private function getWeatherInfo(BicycleStation $station): string
    {
        try {
            if (!$station->getLocation()) {
                return "Sunny with light breeze";
            }
            
            $lat = $station->getLocation()->getLatitude();
            $lon = $station->getLocation()->getLongitude();
            
            $apiKey = $this->getParameter('app.openweather_api_key');
            
            if (!$apiKey) {
                $this->logger->warning("No OpenWeather API key found, using default weather");
                return "Sunny with light breeze";
            }
            
            $url = "https://api.openweathermap.org/data/2.5/weather?lat={$lat}&lon={$lon}&units=metric&appid={$apiKey}";
            
            $response = @file_get_contents($url);
            
            if ($response === false) {
                $this->logger->warning("Failed to connect to OpenWeather API, using default weather");
                return "Sunny with light breeze";
            }
            
            $weatherData = json_decode($response, true);
            
            if (!$weatherData || !isset($weatherData['weather'][0]['description'])) {
                $this->logger->warning("Invalid weather data from API, using default weather");
                return "Sunny with light breeze";
            }
            
            $description = ucfirst($weatherData['weather'][0]['description']);
            $temperature = round($weatherData['main']['temp']) . '°C';
            $windSpeed = isset($weatherData['wind']['speed']) ? 
                round($weatherData['wind']['speed'] * 3.6) . ' km/h' : 'light';
                
            return "{$description}, {$temperature} with {$windSpeed} wind";
        } catch (\Exception $e) {
            $this->logger->error("Weather service error: " . $e->getMessage());
            return "Sunny with light breeze";
        }
    }
}