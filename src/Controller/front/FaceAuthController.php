<?php

namespace App\Controller\front;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;
use Symfony\Component\Security\Http\Authenticator\FormLoginAuthenticator;
use Symfony\Component\HttpClient\HttpClient;
use Psr\Log\LoggerInterface;

class FaceAuthController extends AbstractController
{
    private $entityManager;
    private $userRepository;
    private $logger;

    public function __construct(
        EntityManagerInterface $entityManager,
        UserRepository $userRepository,
        LoggerInterface $logger
    ) {
        $this->entityManager = $entityManager;
        $this->userRepository = $userRepository;
        $this->logger = $logger;
    }

    #[Route('/login/face', name: 'app_login_face', methods: ['GET'])]
    public function loginFace(Request $request): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_front_home');
        }
        
        // Get the error from flash messages if any
        $error = null;
        if ($request->getSession()->has('error')) {
            $error = $request->getSession()->get('error')[0];
            $request->getSession()->remove('error');
        }

        return $this->render('security/login_face_direct.html.twig', [
            'error' => $error
        ]);
    }

    #[Route('/login/face/verify', name: 'app_face_verify', methods: ['POST'])]
    public function verifyFace(Request $request, UserAuthenticatorInterface $userAuthenticator): JsonResponse
    {
        try {
            // Check if it's an XML HTTP Request
            if (!$request->isXmlHttpRequest()) {
                $this->logger->warning('FaceAuth: Non-AJAX request detected to face verification endpoint');
            }

            $data = json_decode($request->getContent(), true);
            
            if (!isset($data['faceImage'])) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'No face image provided'
                ], 400, [
                    'Content-Type' => 'application/json'
                ]);
            }

            $faceImage = $data['faceImage'];
            
            // Log the attempt
            $this->logger->info('FaceAuth: Face verification attempt');
            
            // Verify face with facial recognition API without requiring email
            try {
                $client = HttpClient::create([
                    'timeout' => 30,
                    'max_redirects' => 0,  // Don't follow redirects
                    'verify_peer' => false, // Don't verify SSL certificate for local connections
                    'verify_host' => false  // Don't verify hostname for local connections
                ]);
                
                $this->logger->info('FaceAuth: Attempting to connect to facial recognition API');
                
                // Set explicit timeout and set headers to expect JSON
                $response = $client->request('POST', 'http://localhost:5001/verify', [
                    'json' => [
                        'image' => $faceImage,
                        'face_image' => $faceImage
                        // No user_id/email needed now
                    ],
                    'headers' => [
                        'Accept' => 'application/json',
                        'Content-Type' => 'application/json',
                    ]
                ]);
                
                $statusCode = $response->getStatusCode();
                $content = $response->getContent(false);
                
                $this->logger->info('FaceAuth: Facial recognition API response', [
                    'status_code' => $statusCode,
                    'content_type' => $response->getHeaders()['content-type'][0] ?? 'unknown'
                ]);
                
                // Check for successful HTTP status code
                if ($statusCode !== 200) {
                    $this->logger->error('FaceAuth: API returned non-200 status code', [
                        'status_code' => $statusCode
                    ]);
                    
                    return new JsonResponse([
                        'success' => false,
                        'message' => 'Face recognition service returned an error: HTTP ' . $statusCode
                    ], 500, [
                        'Content-Type' => 'application/json'
                    ]);
                }
            } catch (\Exception $apiException) {
                // Log the API connection error
                $this->logger->error('FaceAuth: Failed to connect to facial recognition API', [
                    'error' => $apiException->getMessage()
                ]);
                
                // Return an error response - do not allow login when API fails
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Face recognition system is currently unavailable. Please try password login instead.',
                    'error_details' => 'API connection failed: ' . $apiException->getMessage(),
                    'api_status' => 'offline'
                ], 503, [
                    'Content-Type' => 'application/json'
                ]);
            }
            
            $result = json_decode($content, true);
            
            // Validate the response from the API
            if (!is_array($result)) {
                $this->logger->error('FaceAuth: Invalid JSON response from API', [
                    'response' => substr($content, 0, 100)
                ]);
                
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Invalid response from facial recognition system'
                ], 500, [
                    'Content-Type' => 'application/json'
                ]);
            }
            
            if (!isset($result['verified']) || $result['verified'] !== true || !isset($result['user_id'])) {
                return new JsonResponse([
                    'success' => false,
                    'message' => $result['message'] ?? 'Face verification failed'
                ], 401, [
                    'Content-Type' => 'application/json'
                ]);
            }
            
            // Face verification successful, get user by email/user_id
            $email = $result['user_id']; // In the Python API, user_id is the email
            $user = $this->userRepository->findOneBy(['email' => $email]);
            
            if (!$user) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'No matching user found for this face'
                ], 401, [
                    'Content-Type' => 'application/json'
                ]);
            }
            
            // Check if face recognition is enabled for this user
            if (!$user->isFaceRecognitionEnabled()) {
                $this->logger->warning('FaceAuth: User attempted to login with face but face recognition is not enabled', [
                    'user_id' => $user->getId_user(),
                    'email' => $user->getEmail()
                ]);
                
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Face recognition is not enabled for your account. Please use password login.'
                ], 401, [
                    'Content-Type' => 'application/json'
                ]);
            }
            
            // Check if user is verified and active
            if (!$user->isVerified()) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Account is not verified. Please check your email for the verification link.'
                ], 401, [
                    'Content-Type' => 'application/json'
                ]);
            }
            
            if ($user->getAccountStatus() !== 'ACTIVE') {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Account is not active. Please contact support.'
                ], 401, [
                    'Content-Type' => 'application/json'
                ]);
            }
            
            // Login the user
            $token = new UsernamePasswordToken($user, 'main', $user->getRoles());
            $this->container->get('security.token_storage')->setToken($token);
            $this->container->get('session')->set('_security_main', serialize($token));
            
            $this->logger->info('FaceAuth: User logged in successfully', [
                'user_id' => $user->getId_user()
            ]);
            
            return new JsonResponse([
                'success' => true,
                'message' => 'Face authentication successful!',
                'redirect' => $this->generateUrl('app_front_home')
            ], 200, [
                'Content-Type' => 'application/json'
            ]);
            
        } catch (\Exception $e) {
            $this->logger->error('Face login error: ' . $e->getMessage());
            return new JsonResponse([
                'success' => false,
                'message' => 'An error occurred during face verification: ' . $e->getMessage()
            ], 500, [
                'Content-Type' => 'application/json'
            ]);
        }
    }

    #[Route('/profile/register-face', name: 'front_profile_register_face', methods: ['POST'])]
    public function registerFace(Request $request): JsonResponse
    {
        try {
            $user = $this->getUser();
            if (!$user) {
                return $this->json([
                    'success' => false,
                    'message' => 'You must be logged in to register your face'
                ], 401);
            }
            
            $faceImage = $request->request->get('faceImage');
            if (!$faceImage) {
                return $this->json([
                    'success' => false,
                    'message' => 'No face image provided'
                ], 400);
            }
            
            // Log details for debugging
            $this->logger->info('FaceAuth: Registering face for user', [
                'user_id' => $user->getId_user(),
                'image_size' => strlen($faceImage)
            ]);
            
            // Register face with facial recognition API
            $client = HttpClient::create(['timeout' => 30]);
            $response = $client->request('POST', 'http://localhost:5001/register', [
                'json' => [
                    'image' => $faceImage,
                    'user_id' => $user->getEmail(),
                    'metadata' => [
                        'name' => $user->getName(),
                        'id' => $user->getId_user()
                    ]
                ]
            ]);
            
            $statusCode = $response->getStatusCode();
            $content = $response->getContent(false);
            
            $this->logger->info('FaceAuth: Facial recognition API response', [
                'status_code' => $statusCode,
                'response' => substr($content, 0, 100) . (strlen($content) > 100 ? '...' : '')
            ]);
            
            $result = json_decode($content, true);
            if (!is_array($result)) {
                throw new \Exception('Invalid JSON response from facial recognition API: ' . substr($content, 0, 100));
            }
            
            if ((isset($result['success']) && $result['success'] === true) || 
                (isset($result['registered']) && $result['registered'] === true)) {
                // Face registration successful, update user
                $user->setFaceRecognitionEnabled(true);
                
                $this->entityManager->flush();
                
                return $this->json([
                    'success' => true,
                    'message' => 'Face registered successfully!',
                    'face_id' => $result['face_id'] ?? $result['user_id'] ?? null
                ]);
            }
            
            return $this->json([
                'success' => false,
                'message' => $result['message'] ?? 'Face registration failed'
            ], 400);
            
        } catch (\Exception $e) {
            $this->logger->error('Face registration error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return $this->json([
                'success' => false,
                'message' => 'An error occurred during face registration: ' . $e->getMessage()
            ], 500);
        }
    }

    #[Route('/profile/disable-face', name: 'app_profile_disable_face', methods: ['POST'])]
    public function disableFace(Request $request): JsonResponse
    {
        try {
            $user = $this->getUser();
            if (!$user) {
                return $this->json([
                    'success' => false,
                    'message' => 'You must be logged in to disable face recognition'
                ], 401);
            }
            
            // Validate CSRF token
            $submittedToken = $request->headers->get('X-CSRF-TOKEN');
            if (!$this->isCsrfTokenValid('disable-face', $submittedToken)) {
                return $this->json([
                    'success' => false,
                    'message' => 'Invalid CSRF token'
                ], 400);
            }
            
            // Delete face from facial recognition API by email
            $client = HttpClient::create();
            $response = $client->request('DELETE', 'http://localhost:5001/delete', [
                'json' => [
                    'email' => $user->getEmail()
                ]
            ]);
            
            // Even if API call fails, we still want to disable face recognition for the user
            try {
                $result = json_decode($response->getContent(), true);
                // Check for both success and deleted properties since the API might use either one
                if ((isset($result['success']) && !$result['success']) || 
                    (isset($result['deleted']) && !$result['deleted'])) {
                    $this->logger->warning('Failed to delete face from API: ' . ($result['message'] ?? 'Unknown error'));
                }
            } catch (\Exception $e) {
                $this->logger->warning('Error while deleting face from API: ' . $e->getMessage());
            }
            
            // Disable face recognition for user
            $user->setFaceRecognitionEnabled(false);
            
            $this->entityManager->flush();
            
            return $this->json([
                'success' => true,
                'message' => 'Face recognition disabled successfully'
            ]);
            
        } catch (\Exception $e) {
            $this->logger->error('Disable face recognition error: ' . $e->getMessage());
            return $this->json([
                'success' => false,
                'message' => 'An error occurred while disabling face recognition'
            ], 500);
        }
    }

    #[Route('/login/face/check', name: 'app_face_check', methods: ['POST'])]
    public function checkFaceApi(Request $request): JsonResponse
    {
        try {
            // This is a diagnostic endpoint to check if the API is responding correctly
            $this->logger->info('FaceAuth: API check requested');
            
            // Check facial API connectivity
            $apiStatus = 'unknown';
            $apiMessage = '';
            
            try {
                $client = HttpClient::create([
                    'timeout' => 5, // Short timeout for quick check
                    'verify_peer' => false,
                    'verify_host' => false
                ]);
                
                $response = $client->request('GET', 'http://localhost:5001/health', [
                    'headers' => [
                        'Accept' => 'application/json'
                    ]
                ]);
                
                if ($response->getStatusCode() === 200) {
                    $apiStatus = 'online';
                    $apiMessage = 'Connected to facial recognition API successfully';
                    
                    // Also check if API can perform face recognition
                    try {
                        // Send a test verification with a small blank image
                        $testImage = "data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7";
                        $verifyResponse = $client->request('POST', 'http://localhost:5001/verify', [
                            'json' => [
                                'image' => $testImage,
                                'test_mode' => true
                            ],
                            'headers' => [
                                'Accept' => 'application/json',
                                'Content-Type' => 'application/json'
                            ]
                        ]);
                        
                        $verifyData = json_decode($verifyResponse->getContent(false), true);
                        
                        // Check if the API is ready using either 'ready' or 'verified'
                        $isServiceReady = (isset($verifyData['ready']) && $verifyData['ready'] === true) || 
                                        (isset($verifyData['verified']) && $verifyData['verified'] === true);
                                        
                        if (!$isServiceReady) {
                            $apiStatus = 'degraded';
                            $apiMessage = 'Facial recognition API is online but facial verification is not ready';
                        }
                    } catch (\Exception $verifyEx) {
                        $apiStatus = 'degraded';
                        $apiMessage = 'Facial recognition API is online but facial verification failed: ' . $verifyEx->getMessage();
                    }
                } else {
                    $apiStatus = 'error';
                    $apiMessage = 'Facial recognition API returned unexpected status: ' . $response->getStatusCode();
                }
            } catch (\Exception $e) {
                $apiStatus = 'offline';
                $apiMessage = 'Failed to connect to facial recognition API: ' . $e->getMessage();
                $this->logger->warning('FaceAuth: API connectivity check failed', [
                    'error' => $e->getMessage()
                ]);
            }
            
            // Return a simple JSON response with diagnostic data
            return new JsonResponse([
                'success' => true,
                'message' => 'API connection successful',
                'timestamp' => new \DateTime(),
                'request_type' => $request->isXmlHttpRequest() ? 'XHR' : 'Standard',
                'api_status' => $apiStatus,
                'api_message' => $apiMessage,
                'server_info' => [
                    'php_version' => PHP_VERSION,
                    'symfony_environment' => $this->getParameter('kernel.environment'),
                    'server_time' => date('Y-m-d H:i:s')
                ]
            ], 200, [
                'Content-Type' => 'application/json'
            ]);
            
        } catch (\Exception $e) {
            $this->logger->error('Face API check error: ' . $e->getMessage());
            return new JsonResponse([
                'success' => false,
                'message' => 'API check failed: ' . $e->getMessage()
            ], 500, [
                'Content-Type' => 'application/json'
            ]);
        }
    }
}