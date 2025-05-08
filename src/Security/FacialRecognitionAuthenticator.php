<?php

namespace App\Security;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\CustomCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class FacialRecognitionAuthenticator extends AbstractAuthenticator implements AuthenticationEntryPointInterface
{
    private $urlGenerator;
    private $entityManager;
    private $userRepository;
    private $httpClient;

    public function __construct(
        UrlGeneratorInterface $urlGenerator, 
        EntityManagerInterface $entityManager,
        UserRepository $userRepository,
        HttpClientInterface $httpClient
    ) {
        $this->urlGenerator = $urlGenerator;
        $this->entityManager = $entityManager;
        $this->userRepository = $userRepository;
        $this->httpClient = $httpClient;
    }

    public function supports(Request $request): ?bool
    {
        // Only support face verification, not the diagnostic check endpoint
        if ($request->getPathInfo() === '/login/face/check') {
            return false;
        }
        
        return $request->getPathInfo() === '/login/face/verify' && $request->isMethod('POST');
    }

    public function authenticate(Request $request): Passport
    {
        // Get JSON content for the request
        $data = json_decode($request->getContent(), true);
        
        // Get face image from either form data or JSON body
        $faceImage = $request->request->get('faceImage') ?? $data['faceImage'] ?? null;
        
        if (empty($faceImage)) {
            throw new AuthenticationException('Face image is required');
        }
        
        // For direct face login, we don't require an email - we'll find the user by their face
        return new Passport(
            new UserBadge('face_login', function ($userIdentifier) use ($faceImage) {
                try {
                    // Call facial recognition API to identify the user
                    $response = $this->httpClient->request('POST', 'http://localhost:5001/verify', [
                        'json' => [
                            'image' => $faceImage
                        ],
                        'timeout' => 30 // Increase timeout
                    ]);
                    
                    $responseContent = $response->getContent(false);
                    error_log('FACE AUTH DEBUG - API response content: ' . $responseContent);
                    error_log('FACE AUTH DEBUG - API response status: ' . $response->getStatusCode());
                    
                    $result = json_decode($responseContent, true);
                    
                    // Validate the API response
                    if (!is_array($result)) {
                        throw new UserNotFoundException('Invalid response from facial recognition service: ' . $responseContent);
                    }
                    
                    // Log the response for debugging
                    error_log('Facial recognition API response: ' . print_r($result, true));
                    
                    // Handle the specific response format from the Python API
                    // The API returns 'verified' instead of 'success' and 'user_id' instead of 'email'
                    if (!isset($result['verified']) || $result['verified'] !== true) {
                        throw new AuthenticationException('Face verification failed: ' . ($result['message'] ?? 'Unknown error'));
                    }
                    
                    // Get the user by email from the response (user_id in the API is the email)
                    if (!isset($result['user_id'])) {
                        throw new UserNotFoundException('No user identifier found in API response');
                    }
                    
                    $userEmail = $result['user_id'];
                    $user = $this->userRepository->findOneBy(['email' => $userEmail]);
                    
                    if (!$user) {
                        throw new UserNotFoundException('No user found with the email associated with this face: ' . $userEmail);
                    }
                    
                    // Verify that face recognition is enabled for this user
                    if (!$user->isFaceRecognitionEnabled()) {
                        throw new AuthenticationException('Facial recognition is not enabled for this user');
                    }
                    
                    return $user;
                } catch (\Exception $e) {
                    // Log the error
                    error_log('Facial recognition error: ' . $e->getMessage());
                    throw new AuthenticationException('Error during facial recognition: ' . $e->getMessage());
                }
            }),
            new CustomCredentials(
                function ($credentials, User $user) {
                    // User is already verified by the UserBadge callback
                    return true;
                },
                $faceImage
            ),
            [
                new RememberMeBadge()
            ]
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        // Redirect to a success page
        return new RedirectResponse($this->urlGenerator->generate('app_front_home'));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        if ($request->isXmlHttpRequest()) {
            $response = new Response(json_encode([
                'success' => false,
                'message' => $exception->getMessage()
            ]), Response::HTTP_UNAUTHORIZED);
            $response->headers->set('Content-Type', 'application/json');
            return $response;
        }

        // Redirect back to the login page with an error message
        return new RedirectResponse($this->urlGenerator->generate('app_login_face', [
            'error' => $exception->getMessage()
        ]));
    }

    public function start(Request $request, AuthenticationException $authException = null): Response
    {
        return new RedirectResponse($this->urlGenerator->generate('app_login_face'));
    }
}
