<?php

namespace App\Service;

use App\Entity\Trip;
use Psr\Log\LoggerInterface;

class PredictPrice
{
    private DeepSeekService $deepSeekService;
    private LoggerInterface $logger;

    public function __construct(
        DeepSeekService $deepSeekService,
        LoggerInterface $logger
    ) {
        $this->deepSeekService = $deepSeekService;
        $this->logger = $logger;
    }

    public function predict(Trip $trip): ?float
    {
        $this->logger->info('Predicting price using DeepSeekService', [
            'departureCity' => $trip->getDepartureCity(),
            'arrivalCity' => $trip->getArrivalCity(),
            'availableSeats' => $trip->getAvailableSeats(),
        ]);

        try {
            return $this->deepSeekService->predictPrice($trip, $this->deepSeekService->apiKey);
        } catch (\Exception $e) {
            $this->logger->error('Error in DeepSeekService', [
                'exception' => $e->getMessage(),
            ]);
            return null;
        }
    }
}