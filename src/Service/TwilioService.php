<?php

namespace App\Service;

use Twilio\Rest\Client;

class TwilioService
{
    private $client;
    private $fromNumber;

    public function __construct(string $accountSid, string $authToken, string $fromNumber)
    {
        $this->client = new Client($accountSid, $authToken);
        $this->fromNumber = $fromNumber;
    }

    public function sendSms(string $to, string $message): void
    {
        $this->client->messages->create(
            $to,
            [
                'from' => $this->fromNumber,
                'body' => $message,
            ]
        );
    }
}