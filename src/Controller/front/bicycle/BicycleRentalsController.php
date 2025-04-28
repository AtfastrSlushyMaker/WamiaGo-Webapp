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
        $user = $this->entityManager->getRepository(User::class)->find(1);
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
                    // Create reservation
                    $rental = $this->rentalService->reserveBicycle(
                        $this->entityManager->getRepository(User::class)->find(1),
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
        $user = $this->entityManager->getRepository(User::class)->find(1);

        if (!$rental || $rental->getUser() !== $user) {
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
        $user = $this->entityManager->getRepository(User::class)->find(1);

        if (!$rental || $rental->getUser() !== $user) {
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
        $user = $this->entityManager->getRepository(User::class)->find(1);

        if (!$rental || $rental->getUser() !== $user) {
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
        $user = $this->entityManager->getRepository(User::class)->find(1);
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
        
        // Validate required parameters
        if (!isset($data['startStationId']) || !isset($data['endStationId']) || !isset($data['bicycleId'])) {
            return new JsonResponse(['error' => 'Missing required parameters'], Response::HTTP_BAD_REQUEST);
        }
        
        // Find the entities
        $startStation = $this->entityManager->getRepository(BicycleStation::class)->find($data['startStationId']);
        $endStation = $this->entityManager->getRepository(BicycleStation::class)->find($data['endStationId']);
        $bicycle = $this->entityManager->getRepository(Bicycle::class)->find($data['bicycleId']);
        
        // Validate entities exist
        if (!$startStation || !$endStation || !$bicycle) {
            return new JsonResponse(['error' => 'One or more requested resources not found'], Response::HTTP_NOT_FOUND);
        }
        
        try {
            // Get bicycle info for pricing calculation
            $bicycleInfo = $this->getBicycleDisplayInfo($bicycle);
            $hourlyRate = $bicycleInfo['hourlyRate'];
            
            // Get location coordinates for more accurate calculations
            $startLat = $startStation->getLocation() ? $startStation->getLocation()->getLatitude() : 0;
            $startLon = $startStation->getLocation() ? $startStation->getLocation()->getLongitude() : 0;
            $endLat = $endStation->getLocation() ? $endStation->getLocation()->getLatitude() : 0;
            $endLon = $endStation->getLocation() ? $endStation->getLocation()->getLongitude() : 0;
            
            // Calculate distance between stations using their locations
            $distance = $this->calculateDistance(
                $startLat,
                $startLon,
                $endLat,
                $endLon
            );
            
            $this->logger->info('Starting AI prediction', [
                'from' => $startStation->getName(),
                'to' => $endStation->getName(),
                'distance' => $distance,
                'bicycle_id' => $bicycle->getIdBike()
            ]);
            
            // Call the AI prediction service
            $prediction = $this->getPredictionFromAI(
                $startStation,
                $endStation,
                $bicycle,
                $distance,
                $hourlyRate
            );
            
            // Check if this is a default prediction (error case)
            if (isset($prediction['is_default']) && $prediction['is_default']) {
                $errorReason = $prediction['error_reason'] ?? 'Unknown error';
                $this->logger->warning('Using fallback prediction', ['reason' => $errorReason]);
                
                // Still return the prediction but with an informative message
                $prediction['_message'] = "We've provided an estimated prediction as we couldn't generate a precise one. Reason: {$errorReason}";
            }
            
            return new JsonResponse($prediction);
            
        } catch (\Exception $e) {
            $this->logger->error('Exception in rental prediction', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Create a user-friendly error message with enough details to be helpful
            $errorMessage = 'Unable to generate prediction. ';
            
            // Add some context based on the error type
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
     * Helper method to calculate distance between two points
     */
    private function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        // Haversine formula to calculate distance between two points
        $earthRadius = 6371; // in kilometers
        
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        
        $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon/2) * sin($dLon/2);
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        $distance = $earthRadius * $c;
        
        return round($distance, 2);
    }
    
    /**
     * Generate prediction using AI (Gemini API)
     */
    private function getPredictionFromAI(
        BicycleStation $startStation,
        BicycleStation $endStation,
        Bicycle $bicycle,
        float $distance,
        float $hourlyRate
    ): array {
        // Get weather information - in a real application you would use your weather service
        $weatherInfo = "Sunny with light breeze"; // This would normally come from your weather API
        
        // Create a detailed prompt for Gemini API
        $prompt = $this->createAIPrompt(
            $startStation,
            $endStation,
            $bicycle,
            $distance,
            $hourlyRate,
            $weatherInfo
        );
        
        // Call Gemini API
        $response = $this->geminiApiService->generateContent($prompt);
        
        // Process Gemini API response
        if (isset($response['error'])) {
            throw new \Exception('AI Prediction Error: ' . $response['error']);
        }
        
        // Extract the prediction from the response
        $prediction = $this->parseGeminiResponse($response);
        
        // If we couldn't parse the response properly, use fallback logic
        if (empty($prediction) || !isset($prediction['estimatedDuration'])) {
            return $this->getFallbackPrediction($distance, $bicycle, $hourlyRate);
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
        string $weatherInfo
    ): string {
        // Get location coordinates for more accurate calculations
        $startLat = $startStation->getLocation() ? $startStation->getLocation()->getLatitude() : 0;
        $startLon = $startStation->getLocation() ? $startStation->getLocation()->getLongitude() : 0;
        $endLat = $endStation->getLocation() ? $endStation->getLocation()->getLatitude() : 0;
        $endLon = $endStation->getLocation() ? $endStation->getLocation()->getLongitude() : 0;
        
        return <<<EOT
Act as a bicycle rental prediction system for an electric bicycle service.
Please predict and analyze a rental trip with these accurate details:

Starting point: {$startStation->getName()} (coordinates: {$startLat}, {$startLon})
Destination: {$endStation->getName()} (coordinates: {$endLat}, {$endLon})
Calculated straight-line distance: approximately {$distance} km
Electric bicycle battery level: {$bicycle->getBatteryLevel()}%
Current range: {$bicycle->getRangeKm()} km
Weather conditions: {$weatherInfo}
Hourly rental rate: {$hourlyRate} TND

Important information:
- Batteries can be recharged at any bicycle station if needed during the trip.
- The average cycling speed is about 15km/h in good conditions.
- Road conditions typically make the actual cycling distance 15-30% longer than the straight-line distance.
- Battery consumption is roughly 5-8% per kilometer.
- For accurate prediction, calculate your own estimated road distance between the coordinates.
- Factor in traffic,distance, terrain, and weather conditions for a realistic price estimate.

Provide a comprehensive analysis including all of the following (leave no field empty):
1. Your own calculated distance estimate in kilometers based on the coordinates
2. Estimated duration in minutes considering traffic, terrain, and weather
3. Estimated cost based on the hourly rate and predicted duration
4. Detailed weather impact analysis (how specific weather conditions will affect the ride)
5. Expected battery consumption percentage for this trip
6. Remaining range in km after the trip
7. Route recommendation including at least one notable landmark or area to pass through
8. Safety tips specific to this route and current conditions
9. Health benefits estimate (calories burned, exercise intensity)
10. If battery might be insufficient, suggest recharging options at stations along the route
11. Points of interest along the route that the rider might want to visit yuo can get this from google maps or make a search on the route
12. Traffic conditions that might affect the journey search this if avaialble 
13. Potential rest stops or cafés along the way you get this from google maps or make a search on the route
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
     * Parse the Gemini API response to extract the prediction data
     */
    private function parseGeminiResponse(array $response): array
    {
        try {
            // Extract text from Gemini response
            $text = $response['candidates'][0]['content']['parts'][0]['text'] ?? '';
            
            if (empty($text)) {
                $this->logger->warning('Gemini API returned empty text content');
                return $this->createDefaultPrediction('Empty API response text');
            }

            $this->logger->info('Raw Gemini response text', ['text' => substr($text, 0, 1000)]);
            
            // Extract JSON from the text (might be surrounded by markdown code blocks)
            $jsonText = '';
            if (preg_match('/```(?:json)?\s*(.*?)\s*```/s', $text, $matches)) {
                $jsonText = trim($matches[1]);
                $this->logger->info('Successfully extracted JSON from markdown code block');
            } else if (preg_match('/({[\s\S]*})/s', $text, $matches)) {
                $jsonText = trim($matches[1]);
                $this->logger->info('Successfully extracted JSON directly from text');
            } else {
                $this->logger->warning('Could not extract JSON from Gemini response', ['text' => substr($text, 0, 500)]);
                return $this->createDefaultPrediction('JSON extraction failed');
            }
            
            $this->logger->info('Extracted JSON text', ['json' => $jsonText]);
            
            // Parse JSON
            $data = json_decode($jsonText, true);
            
            // Check if JSON parsing was successful
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->logger->error('JSON parsing failed', [
                    'error' => json_last_error_msg(),
                    'jsonText' => $jsonText
                ]);
                return $this->createDefaultPrediction('JSON parsing error: ' . json_last_error_msg());
            }
            
            if (!is_array($data)) {
                $this->logger->error('Parsed JSON is not an array', ['data' => var_export($data, true)]);
                return $this->createDefaultPrediction('Invalid JSON structure');
            }
            
            $this->logger->info('Successfully parsed JSON data', ['data' => $data]);
            
            // Normalize data to handle arrays returned by Gemini
            $data = $this->normalizeGeminiData($data);
            
            // Calculate realistic values based on actual distance between stations
            // If AI didn't provide a distance, calculate it ourselves
            $calculatedDistance = $this->parseDistanceFromResponse($data, $text);
            $this->logger->info('Calculated distance', ['distance' => $calculatedDistance]);
            
            // Use AI's duration if provided and reasonable, otherwise calculate based on distance
            $estimatedDuration = $this->parseDurationFromResponse($data, $calculatedDistance);
            $this->logger->info('Estimated duration', ['duration' => $estimatedDuration]);
            
            // Calculate cost based on duration
            $estimatedCost = $this->parseCostFromResponse($data, $estimatedDuration);
            $this->logger->info('Estimated cost', ['cost' => $estimatedCost]);
            
            // Battery consumption based on distance
            $batteryConsumption = $this->parseBatteryConsumptionFromResponse($data, $calculatedDistance);
            
            // Remaining range after trip
            $rangeAfterTrip = $this->parseRangeAfterTripFromResponse($data, $calculatedDistance);
            
            // Prepare the response with all required fields
            $prediction = [
                'distance' => $calculatedDistance,
                'estimatedDuration' => $estimatedDuration, 
                'estimatedCost' => $estimatedCost,
                'weatherImpact' => $data['weatherImpact'] ?? 'Mixed conditions that may affect cycling speed slightly.',
                'batteryConsumption' => $batteryConsumption,
                'rangeAfterTrip' => $rangeAfterTrip
            ];
            
            // Enhanced fields with defaults if missing
            $prediction['routeSuggestion'] = $data['routeSuggestion'] ?? 'Take the main bike path connecting the stations, following the safest urban route.';
            $prediction['safetyTips'] = $data['safetyTips'] ?? 'Wear a helmet and use bike lanes where available. Stay visible to traffic.';
            $prediction['healthBenefits'] = $data['healthBenefits'] ?? "Burns approximately " . $this->calculateCalories($calculatedDistance) . " calories, providing good cardiovascular exercise.";
            $prediction['difficultyLevel'] = $data['difficultyLevel'] ?? $this->calculateDifficultyLevel($calculatedDistance);
            
            // Recharging guidance
            $prediction['rechargingNeeded'] = isset($data['rechargingNeeded']) ? 
                (bool) $data['rechargingNeeded'] : 
                ($batteryConsumption > 75);
                
            $prediction['rechargingSuggestion'] = $data['rechargingSuggestion'] ?? 
                ($prediction['rechargingNeeded'] ? 
                'Consider stopping at a station halfway through your journey to recharge.' : 
                'No recharging needed for this trip.');
            
            $this->logger->info('Final prediction data', ['prediction' => $prediction]);
            return $prediction;
        } catch (\Exception $e) {
            $this->logger->error('Exception in parseGeminiResponse', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->createDefaultPrediction('Exception: ' . $e->getMessage());
        }
    }
    
    /**
     * Create a default prediction when JSON parsing fails
     */
    private function createDefaultPrediction(string $reason): array
    {
        $this->logger->warning('Using default prediction', ['reason' => $reason]);
        
        // Default distance for calculations
        $defaultDistance = 3.0;
        
        // Calculate realistic values
        $duration = $this->parseDurationFromResponse([], $defaultDistance);
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
    private function parseDistanceFromResponse(array $data, string $text): float
    {
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
        
        // If we get here, use the calculated distance or a default
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
        // Try to use AI's range if available and reasonable
        if (isset($data['rangeAfterTrip']) && is_numeric($data['rangeAfterTrip'])) {
            $range = (float) $data['rangeAfterTrip'];
            
            // Sanity check: range should make sense given the distance
            $fullRange = 30.0;
            $expectedRange = max(0, $fullRange - $distance);
            
            // If AI's range is within 30% of calculated range, use it
            if ($range >= ($expectedRange * 0.7) && $range <= ($expectedRange * 1.3)) {
                return round($range, 1);
            }
        }
        
        // Calculate range after trip based on full range and distance
        $fullRange = mt_rand(28, 32); // Full range of 28-32km with some variation
        $remainingRange = max(0, $fullRange - $distance);
        return round($remainingRange, 1);
    }
    
    /**
     * Calculate calories burned based on distance
     */
    private function calculateCalories(float $distance): string
    {
        // About 30-50 calories per km
        $caloriesPerKm = mt_rand(30, 50);
        $totalCalories = round($distance * $caloriesPerKm);
        
        // Format as a range for more natural display
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
     * 
     * @param array $data Raw data from Gemini API
     * @return array Normalized data with consistent types
     */
    private function normalizeGeminiData(array $data): array
    {
        $normalized = [];
        
        // Handle simple scalar values, potentially coming as arrays
        foreach (['distance', 'estimatedDuration', 'estimatedCost', 'batteryConsumption', 'rangeAfterTrip'] as $key) {
            if (isset($data[$key])) {
                if (is_array($data[$key]) && count($data[$key]) > 0) {
                    $normalized[$key] = is_numeric($data[$key][0]) ? (float)$data[$key][0] : $data[$key][0];
                } else {
                    $normalized[$key] = $data[$key];
                }
            } else {
                // Set defaults for missing data
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
                // Keep as is if it's already a string
                if (is_string($data[$key])) {
                    $normalized[$key] = $data[$key];
                }
                // Extract string from array if it's an array
                else if (is_array($data[$key]) && count($data[$key]) > 0) {
                    $normalized[$key] = is_string($data[$key][0]) ? $data[$key][0] : (string)$data[$key][0];
                }
                // Handle nested description format
                else if (is_array($data[$key]) && isset($data[$key]['description'])) {
                    $normalized[$key] = $data[$key]['description'];
                }
            } else {
                // Default fallback messages based on field
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
                // Convert string 'true'/'false' to actual boolean if needed
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
        
        // Handle the additional info fields introduced in our recent enhancement
        foreach (['pointsOfInterest', 'trafficConditions', 'restStops', 'terrainDescription', 'environmentalImpact'] as $key) {
            if (isset($data[$key])) {
                // Is it already a string?
                if (is_string($data[$key])) {
                    $normalized[$key] = $data[$key];
                }
                // Is it an array that we should convert to string?
                else if (is_array($data[$key])) {
                    // If it's a simple array with one string element
                    if (count($data[$key]) > 0 && is_string($data[$key][0])) {
                        $normalized[$key] = $data[$key][0];
                    }
                    // Otherwise, try to join array elements or convert to string
                    else {
                        $normalized[$key] = is_array($data[$key]) ? implode(", ", array_filter($data[$key], 'is_string')) : (string)$data[$key];
                    }
                }
            } else {
                // Default values for missing fields
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
}