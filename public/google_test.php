<?php
// Test file to verify Google OAuth redirect
header('Content-Type: text/plain');

// Show path and route information
echo "Current script: " . $_SERVER['SCRIPT_NAME'] . "\n";
echo "Requested URI: " . $_SERVER['REQUEST_URI'] . "\n";

// Create a simple redirect to Google
echo "Attempting to redirect to Google...\n";

// Record this attempt
file_put_contents(
    __DIR__ . '/../var/log/google_test.log',
    date('Y-m-d H:i:s') . " - Test file executed\n",
    FILE_APPEND
);

// Redirect to Google's OAuth endpoint with minimal parameters
$clientId = "1040148562384-gqks7lpj3h72oel3vhmkgs34f746ib2j.apps.googleusercontent.com";
$redirectUri = "https://127.0.0.1:8000/connect/google/callback";
$scope = "email profile";
$state = bin2hex(random_bytes(16)); // For CSRF protection

$googleAuthUrl = "https://accounts.google.com/o/oauth2/v2/auth?" . http_build_query([
    'client_id' => $clientId,
    'redirect_uri' => $redirectUri,
    'response_type' => 'code',
    'scope' => $scope,
    'state' => $state
]);

// Log the URL we're redirecting to
file_put_contents(
    __DIR__ . '/../var/log/google_test.log',
    date('Y-m-d H:i:s') . " - Redirecting to: " . $googleAuthUrl . "\n",
    FILE_APPEND
);

// Perform redirect
header('Location: ' . $googleAuthUrl);
exit;
