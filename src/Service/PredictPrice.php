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
        try {
            $this->logger->debug('Attempting price prediction', [
                'trip' => [
                    'departure' => $trip->getDepartureCity(),
                    'arrival' => $trip->getArrivalCity(),
                    'seats' => $trip->getAvailableSeats()
                ]
            ]);

            $price = $this->deepSeekService->predictPrice($trip);

            if ($price === null) {
                $this->logger->warning('Received null price from prediction service');
                return null;
            }

            return $price;
        } catch (\RuntimeException $e) {
            $this->logger->error('Prediction service error: ' . $e->getMessage());
            return null;
        } catch (\Exception $e) {
            $this->logger->critical('Unexpected prediction error: ' . $e->getMessage());
            return null;
        }
    }
}