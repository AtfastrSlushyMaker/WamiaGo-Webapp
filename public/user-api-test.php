<?php

/**
 * Debug script for User API Endpoint
 * Provides direct PHP testing of the User API endpoint
 */

// Display all errors
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo '<h1>User API Debug Utility</h1>';

// Get the ID from the URL parameter
$userId = $_GET['id'] ?? 1;

// Create a proper URL
$apiUrl = "/admin/users/api/{$userId}";

echo "<p>Testing API endpoint: <code>{$apiUrl}</code></p>";

try {
    // Create a cURL request to internal API endpoint
    $ch = curl_init();
    
    // The URL to fetch
    $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https://" : "http://") . $_SERVER['HTTP_HOST'];
    $fullUrl = $baseUrl . $apiUrl;
    
    curl_setopt($ch, CURLOPT_URL, $fullUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    
    // For debugging purposes in local development
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    
    // Execute the request
    $response = curl_exec($ch);
    
    // Get the response code
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    
    // Close the cURL handle
    curl_close($ch);
    
    // Split the response
    $headers = substr($response, 0, $headerSize);
    $body = substr($response, $headerSize);
    
    // Display the response details
    echo "<div style='padding: 10px; margin: 10px 0; background-color: #f5f5f5; border-radius: 5px;'>";
    echo "<h3>Response Status Code: {$httpCode}</h3>";
    
    // Format and display headers
    echo "<h3>Response Headers:</h3>";
    echo "<pre style='background-color: #eee; padding: 10px;'>" . htmlspecialchars($headers) . "</pre>";
    
    // Format and display body
    echo "<h3>Response Body:</h3>";
    
    // Try to pretty print if it's JSON
    if (strpos($headers, 'Content-Type: application/json') !== false) {
        $jsonData = json_decode($body, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            echo "<pre style='background-color: #eee; padding: 10px;'>" . 
                 htmlspecialchars(json_encode($jsonData, JSON_PRETTY_PRINT)) . 
                 "</pre>";
        } else {
            echo "<p>Failed to parse JSON: " . json_last_error_msg() . "</p>";
            echo "<pre style='background-color: #eee; padding: 10px;'>" . 
                 htmlspecialchars($body) . 
                 "</pre>";
        }
    } else {
        echo "<pre style='background-color: #eee; padding: 10px;'>" . 
             htmlspecialchars($body) . 
             "</pre>";
    }
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='padding: 10px; background-color: #ffeeee; color: #cc0000; border-radius: 5px;'>";
    echo "<h3>Error Occurred:</h3>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}

// Form to test different user IDs
echo '<div style="margin-top: 20px; padding: 15px; background-color: #f0f0f0; border-radius: 5px;">';
echo '<h3>Test Different User ID</h3>';
echo '<form method="get" action="">';
echo '<label for="id">User ID:</label> ';
echo '<input type="number" name="id" id="id" value="' . htmlspecialchars($userId) . '" min="1"> ';
echo '<button type="submit">Test</button>';
echo '</form>';
echo '</div>';

// Link back to main debugging page
echo '<div style="margin-top: 20px;">';
echo '<a href="user-api-debug.html" style="display: inline-block; padding: 10px 15px; background-color: #007bff; color: white; text-decoration: none; border-radius: 5px;">Back to Debug Interface</a>';
echo '</div>';
