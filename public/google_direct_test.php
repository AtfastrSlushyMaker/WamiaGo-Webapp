<?php
// This is a direct test for Google OAuth redirection

require dirname(__DIR__).'/vendor/autoload.php';
require dirname(__DIR__).'/config/bootstrap.php';

use App\Kernel;
use Symfony\Component\HttpFoundation\Request;

$kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$request = Request::createFromGlobals();
$kernel->boot();

// Get the container
$container = $kernel->getContainer();

// Get the ClientRegistry service
$clientRegistry = $container->get('knpu.oauth2.registry');

try {
    // Log that we're trying to get the Google client
    file_put_contents(
        __DIR__ . '/../var/log/google_direct_test.log',
        date('Y-m-d H:i:s') . " - Attempting to get Google client\n",
        FILE_APPEND
    );
    
    // Get the Google client
    $googleClient = $clientRegistry->getClient('google');
    
    // Log that we successfully got the client
    file_put_contents(
        __DIR__ . '/../var/log/google_direct_test.log',
        date('Y-m-d H:i:s') . " - Google client obtained\n",
        FILE_APPEND
    );
    
    // Create a redirect response
    $redirectResponse = $googleClient->redirect([
        'email', 'profile'
    ]);
    
    // Log the redirect URL
    file_put_contents(
        __DIR__ . '/../var/log/google_direct_test.log',
        date('Y-m-d H:i:s') . " - Redirect URL: " . $redirectResponse->getTargetUrl() . "\n",
        FILE_APPEND
    );
    
    // Send the response (redirect to Google)
    $redirectResponse->send();
    
} catch (\Exception $e) {
    // Log any errors
    file_put_contents(
        __DIR__ . '/../var/log/google_direct_test.log',
        date('Y-m-d H:i:s') . " - ERROR: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n",
        FILE_APPEND
    );
    
    // Show error
    echo "Error: " . $e->getMessage();
}
