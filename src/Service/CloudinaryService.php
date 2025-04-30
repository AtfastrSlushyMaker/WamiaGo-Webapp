<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class CloudinaryService
{
    private $cloudName;
    private $apiKey;
    private $apiSecret;
    private $httpClient;

    public function __construct(
        string $cloudName, 
        string $apiKey, 
        string $apiSecret, 
        HttpClientInterface $httpClient
    ) {
        $this->cloudName = $cloudName;
        $this->apiKey = $apiKey;
        $this->apiSecret = $apiSecret;
        $this->httpClient = $httpClient;
    }

    /**
     * Upload an image to Cloudinary
     *
     * @param UploadedFile $file
     * @param string|null $publicId Optional public ID for the image
     * @return array|null Returns upload response or null on failure
     */
    public function uploadImage(UploadedFile $file, ?string $publicId = null): ?array
    {
        $timestamp = time();
        $folder = 'wamiango/profile_pictures';
        
        // Parameters for the upload
        $params = [
            'timestamp' => $timestamp,
            'folder' => $folder,
        ];
        
        if ($publicId) {
            $params['public_id'] = $publicId;
        }
        
        // Add signature
        $params['signature'] = $this->generateSignature($params);
        $params['api_key'] = $this->apiKey;
        
        // Prepare the form data
        $formData = [];
        foreach ($params as $key => $value) {
            $formData[$key] = $value;
        }
        
        // Upload the file
        try {
            $response = $this->httpClient->request('POST', "https://api.cloudinary.com/v1_1/{$this->cloudName}/image/upload", [
                'headers' => [
                    'Accept' => 'application/json',
                ],
                'body' => [
                    'file' => fopen($file->getPathname(), 'r'),
                ] + $formData,
            ]);
            
            $statusCode = $response->getStatusCode();
            if ($statusCode === 200) {
                return json_decode($response->getContent(), true);
            }
            
            return null;
        } catch (\Exception $e) {
            // Log error
            error_log("Cloudinary upload error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Generate a signature for Cloudinary API requests
     *
     * @param array $params
     * @return string
     */
    private function generateSignature(array $params): string
    {
        // Sort parameters
        ksort($params);
        
        // Build string to sign
        $stringToSign = '';
        foreach ($params as $key => $value) {
            $stringToSign .= $key . '=' . $value . '&';
        }
        $stringToSign = rtrim($stringToSign, '&');
        
        // Generate signature
        return hash('sha256', $stringToSign . $this->apiSecret);
    }
    
    /**
     * Delete an image from Cloudinary
     *
     * @param string $publicId
     * @return bool Success or failure
     */
    public function deleteImage(string $publicId): bool
    {
        $timestamp = time();
        
        // Parameters for deletion
        $params = [
            'public_id' => $publicId,
            'timestamp' => $timestamp,
        ];
        
        // Add signature
        $params['signature'] = $this->generateSignature($params);
        $params['api_key'] = $this->apiKey;
        
        try {
            $response = $this->httpClient->request('POST', "https://api.cloudinary.com/v1_1/{$this->cloudName}/image/destroy", [
                'headers' => [
                    'Accept' => 'application/json',
                ],
                'body' => $params,
            ]);
            
            $statusCode = $response->getStatusCode();
            $content = json_decode($response->getContent(), true);
            
            return $statusCode === 200 && isset($content['result']) && $content['result'] === 'ok';
        } catch (\Exception $e) {
            // Log error
            error_log("Cloudinary deletion error: " . $e->getMessage());
            return false;
        }
    }
} 