<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\FacialRecognitionService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class FacialRecognitionController extends AbstractController
{
    private $httpClient;
    private $entityManager;
    private $csrfTokenManager;
    private $security;
    private $facialRecognitionService;

    public function __construct(
        HttpClientInterface $httpClient,
        EntityManagerInterface $entityManager,
        CsrfTokenManagerInterface $csrfTokenManager,
        Security $security,
        FacialRecognitionService $facialRecognitionService
    ) {
        $this->httpClient = $httpClient;
        $this->entityManager = $entityManager;
        $this->csrfTokenManager = $csrfTokenManager;
        $this->security = $security;
        $this->facialRecognitionService = $facialRecognitionService;
    }

    #[Route('/login/face', name: 'app_login_face')]
    public function loginFace(AuthenticationUtils $authenticationUtils): Response
    {
        // Get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // Last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login_face.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }    #[Route('/login/face/verify', name: 'app_verify_face', methods: ['POST'])]
    public function verifyFace(Request $request): Response
    {        try {
            $faceImage = $request->request->get('faceImage');
            $email = $request->request->get('email');
            
            // Also check for JSON input if form data is not found
            if (!$faceImage) {
                $data = json_decode($request->getContent(), true);
                $faceImage = $data['faceImage'] ?? null;
                $email = $data['email'] ?? null;
            }

            if (!$faceImage) {
                // Log the request data for debugging
                error_log('Missing parameters in face verification. Got: ' . 
                        'faceImage: ' . (empty($faceImage) ? 'empty' : substr($faceImage, 0, 30) . '...') . ', ' . 
                        'email: ' . ($email ?: 'empty'));
                  if ($request->isXmlHttpRequest()) {
                    return $this->json([
                        'success' => false,
                        'message' => 'Missing faceImage parameter'
                    ], Response::HTTP_BAD_REQUEST);
                } else {
                    $this->addFlash('error', 'Missing faceImage parameter');
                    return $this->redirectToRoute('app_login_face');
                }
            }            try {
                $user = null;
                
                // If email is provided, find that specific user
                if ($email) {
                    $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
                    
                    if (!$user) {
                        if ($request->isXmlHttpRequest()) {
                            return $this->json([
                                'success' => false,
                                'message' => 'User not found'
                            ], Response::HTTP_NOT_FOUND);
                        } else {
                            $this->addFlash('error', 'User not found');
                            return $this->redirectToRoute('app_login_face');
                        }
                    }
                } else {                    // If no email is provided, let the facial recognition service handle this
                    // The verifyFace method of the service will try to match without a user
                    error_log('No email provided, using facial recognition to identify user');
                }
                
                // Verify the face using the service
                $result = $this->facialRecognitionService->verifyFace($faceImage, $user);
                
                // Log the verification result for debugging
                error_log('Face verification result: ' . json_encode($result));
                
                if ($result['verified']) {
                    // Check if a user was found by the facial recognition service
                    if (isset($result['userId']) && !$user) {
                        // Try to find the user by the ID returned from the service
                        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $result['userId']]);
                        
                        if (!$user) {
                            if ($request->isXmlHttpRequest()) {
                                return $this->json([
                                    'success' => false,
                                    'message' => 'Facial recognition successful, but user not found in database'
                                ], Response::HTTP_NOT_FOUND);
                            } else {
                                $this->addFlash('error', 'Facial recognition successful, but user not found in database');
                                return $this->redirectToRoute('app_login_face');
                            }
                        }
                    }
                    
                    // At this point, we should have a valid user
                    if (!$user) {
                        if ($request->isXmlHttpRequest()) {
                            return $this->json([
                                'success' => false,
                                'message' => 'User identification failed after facial recognition'
                            ], Response::HTTP_BAD_REQUEST);
                        } else {
                            $this->addFlash('error', 'User identification failed after facial recognition');
                            return $this->redirectToRoute('app_login_face');
                        }
                    }
                    
                    if (!$user->isFaceRecognitionEnabled()) {
                        if ($request->isXmlHttpRequest()) {
                            return $this->json([
                                'success' => false,
                                'message' => 'Facial recognition is not enabled for this user'
                            ], Response::HTTP_FORBIDDEN);
                        } else {
                            $this->addFlash('error', 'Facial recognition is not enabled for this user');
                            return $this->redirectToRoute('app_login_face');
                        }
                    }

                    // Manually authenticate the user
                    $this->authenticateUser($user, $request);

                    // Handle the response based on the request type
                    if ($request->isXmlHttpRequest()) {
                        // For AJAX requests, return JSON
                        return $this->json([
                            'success' => true,
                            'message' => 'Face verification successful',
                            'redirect' => $this->generateUrl('app_front_home')
                        ]);
                    } else {
                        // For form submissions, redirect directly
                        return $this->redirectToRoute('app_front_home');
                    }
                }

                // Handle verification failure 
                if ($request->isXmlHttpRequest()) {
                    return $this->json([
                        'success' => false,
                        'message' => $result['message'],
                        'confidence' => $result['confidence'] ?? 0
                    ]);
                } else {
                    $this->addFlash('error', $result['message'] ?? 'Face verification failed');
                    return $this->redirectToRoute('app_login_face');
                }
            } catch (\Exception $e) {
                error_log('Face verification exception: ' . $e->getMessage());
                error_log('Stack trace: ' . $e->getTraceAsString());
                
                if ($request->isXmlHttpRequest()) {
                    return $this->json([
                        'success' => false,
                        'message' => 'Error verifying face: ' . $e->getMessage()
                    ], Response::HTTP_INTERNAL_SERVER_ERROR);
                } else {
                    $this->addFlash('error', 'Error verifying face: ' . $e->getMessage());
                    return $this->redirectToRoute('app_login_face');
                }
            }
        } catch (\Throwable $throwable) {
            // Catch-all for any other errors
            error_log('Critical error in face verification: ' . $throwable->getMessage());
            error_log('Stack trace: ' . $throwable->getTraceAsString());
            
            if ($request->isXmlHttpRequest()) {
                return $this->json([
                    'success' => false,
                    'message' => 'A system error occurred. Please try again later.',
                    'error' => $throwable->getMessage()
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            } else {
                $this->addFlash('error', 'A system error occurred. Please try again later.');
                return $this->redirectToRoute('app_login_face');
            }
        }
    }    // Add this helper method to manually authenticate the user
    private function authenticateUser(User $user, Request $request): void
    {
        // Create a token with the user, main firewall name, and user roles
        $token = new UsernamePasswordToken(
            $user, 
            'main', 
            $user->getRoles()
        );
        
        // Get the token storage service from the container and set the token
        $tokenStorage = $this->container->get('security.token_storage');
        $tokenStorage->setToken($token);
        
        // Save the token in the session
        $request->getSession()->set('_security_main', serialize($token));
        
        // Dispatch a login event
        $request->getSession()->set('_security.last_username', $user->getEmail());
        
        error_log('User authenticated: ' . $user->getEmail());
    }

    #[Route('/login/face/complete/{token}', name: 'app_complete_face_login')]
    public function completeFaceLogin(Request $request, Security $security, string $token): Response
    {
        // Check if the token is valid
        $loginData = $request->getSession()->get('face_login_token');
        
        if (!$loginData || $loginData['token'] !== $token || $loginData['expires'] < time()) {
            $this->addFlash('error', 'Invalid or expired login token. Please try again.');
            return $this->redirectToRoute('app_login_face');
        }
        
        // Find the user
        $user = $this->entityManager->getRepository(User::class)->find($loginData['user_id']);
        
        if (!$user) {
            $this->addFlash('error', 'User not found. Please try again.');
            return $this->redirectToRoute('app_login_face');
        }
        
        // Log the user in
        $request->getSession()->remove('face_login_token');
        
        // Return success page
        return $this->render('security/login_face_success.html.twig', [
            'user' => $user
        ]);
    }    #[Route('/profile/register-face', name: 'app_profile_register_face', methods: ['POST'])]
    public function registerFace(Request $request): JsonResponse
    {
        $user = $this->security->getUser();
        
        if (!$user) {
            return $this->json([
                'success' => false,
                'message' => 'User not authenticated'
            ], 401);
        }
        
        $faceImage = $request->request->get('faceImage');
        if (!$faceImage) {
            return $this->json([
                'success' => false,
                'message' => 'No face image provided'
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            // Register the face using the service instead of direct API call
            $success = $this->facialRecognitionService->registerFace($user, $faceImage);
            
            if ($success) {
                // Update user's facial recognition status
                $user->setFaceRecognitionEnabled(true);
                $this->entityManager->flush();

                return $this->json([
                    'success' => true,
                    'message' => 'Face registered successfully'
                ]);
            }

            return $this->json([
                'success' => false,
                'message' => 'Failed to register face'
            ]);
        } catch (\Exception $e) {
            error_log('Error registering face: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());
            
            return $this->json([
                'success' => false,
                'message' => 'Error registering face: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/profile/disable-face', name: 'app_profile_disable_face', methods: ['POST'])]
    public function disableFace(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json([
                'success' => false,
                'message' => 'User not authenticated'
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Validate CSRF token
        $token = $request->headers->get('X-CSRF-TOKEN');
        if (!$this->isCsrfTokenValid('disable-face', $token)) {
            return $this->json([
                'success' => false,
                'message' => 'Invalid CSRF token'
            ], Response::HTTP_FORBIDDEN);
        }

        try {
            // Disable facial recognition using the service
            $success = $this->facialRecognitionService->disableFacialRecognition($user);
            
            // Update the user entity
            $user->setFaceRecognitionEnabled(false);
            $this->entityManager->flush();

            return $this->json([
                'success' => true,
                'message' => 'Facial recognition disabled successfully'
            ]);
        } catch (\Exception $e) {
            // If there's an API error, we still try to disable facial recognition
            $user->setFaceRecognitionEnabled(false);
            $this->entityManager->flush();

            return $this->json([
                'success' => true,
                'message' => 'Facial recognition disabled successfully, but there was an error communicating with the recognition service'
            ]);
        }
    }
    
    #[Route('/profile/face-setup', name: 'app_face_setup')]
    public function faceSetup(): Response
    {
        $user = $this->getUser();
        if (!$user) {
            $this->addFlash('error', 'You must be logged in to access this page');
            return $this->redirectToRoute('app_login');
        }
        
        return $this->render('security/face_setup.html.twig', [
            'user' => $user
        ]);
    }
    
    #[Route('/profile/face-manage', name: 'app_face_manage')]
    public function faceManage(): Response
    {
        $user = $this->getUser();
        if (!$user) {
            $this->addFlash('error', 'You must be logged in to access this page');
            return $this->redirectToRoute('app_login');
        }
        
        if (!$user->isFaceRecognitionEnabled()) {
            return $this->redirectToRoute('app_face_setup');
        }
        
        return $this->render('security/face_manage.html.twig', [
            'user' => $user,
            'csrf_token' => $this->csrfTokenManager->getToken('disable-face')->getValue()
        ]);
    }
}
