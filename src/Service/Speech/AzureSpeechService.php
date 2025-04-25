<?php

namespace App\Service\Speech;

class AzureSpeechService
{
    private $subscriptionKey;
    private $region;
    private $isRecording = false;

    public function __construct()
    {
        // Get environment variables exactly like in JavaFX
        $this->subscriptionKey = getenv('AZURE_SPEECH_KEY');
        $this->region = getenv('AZURE_SPEECH_REGION');

        if (!$this->subscriptionKey || !$this->region) {
            throw new \RuntimeException('AZURE_SPEECH_KEY and AZURE_SPEECH_REGION environment variables must be set.');
        }
    }

    public function getSpeechToken(): array
    {
        $url = "https://{$this->region}.api.cognitive.microsoft.com/sts/v1.0/issuetoken";
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, '');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Ocp-Apim-Subscription-Key: {$this->subscriptionKey}",
            'Content-Type: application/x-www-form-urlencoded'
        ]);

        $token = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($status !== 200 || !$token) {
            throw new \RuntimeException('Failed to obtain Azure Speech token');
        }

        return [
            'token' => $token,
            'region' => $this->region,
            'subscriptionKey' => $this->subscriptionKey
        ];
    }

    public function getConfig(): array
    {
        $tokenData = $this->getSpeechToken();
        return [
            'authToken' => $tokenData['token'],
            'region' => $this->region,
            'language' => 'fr-FR',
            'subscriptionKey' => $this->subscriptionKey
        ];
    }
}