<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class AzureTTSService
{
    private $client;
    private $key;
    private $region;

    public function __construct(HttpClientInterface $client, string $key, string $region)
    {
        $this->client = $client;
        $this->key = $key;
        $this->region = $region;
    }

    public function synthesizeSpeech(string $text): string
    {
        $endpoint = "https://{$this->region}.tts.speech.microsoft.com/cognitiveservices/v1";

        $response = $this->client->request('POST', $endpoint, [
            'headers' => [
                'Ocp-Apim-Subscription-Key' => $this->key,
                'Content-Type' => 'application/ssml+xml',
                'X-Microsoft-OutputFormat' => 'audio-16khz-32kbitrate-mono-mp3',
                'User-Agent' => 'SymfonyTTSClient',
            ],
            'body' => '
                <speak version="1.0" xml:lang="en-US">
                    <voice xml:lang="en-US" xml:gender="Female" name="en-US-AriaNeural">
                        ' . htmlspecialchars($text) . '
                    </voice>
                </speak>
            ',
        ]);

        return $response->getContent();
    }
}
