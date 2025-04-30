<?php

namespace App\Security;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\Exception\TooManyLoginAttemptsAuthenticationException;

class LoginAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    public const LOGIN_ROUTE = 'app_login';
    
    // Track failed login attempts
    private const MAX_ATTEMPTS = 5;
    private const ATTEMPT_INTERVAL = 3600; // 1 hour

    private EntityManagerInterface $entityManager;

    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
        EntityManagerInterface $entityManager
    ) {
        $this->entityManager = $entityManager;
    }

    public function authenticate(Request $request): Passport
    {
        $email = $request->request->get('email', '');
        $password = $request->request->get('password', '');
        $csrfToken = $request->request->get('_csrf_token');

        // Validate email format
        if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new CustomUserMessageAuthenticationException('Please enter a valid email address.');
        }

        // Check for empty fields
        if (empty($email)) {
            throw new CustomUserMessageAuthenticationException('Email cannot be empty.');
        }

        if (empty($password)) {
            throw new CustomUserMessageAuthenticationException('Password cannot be empty.');
        }
        
        // Check for brute force attempts
        $session = $request->getSession();
        $attemptKey = '_security.login_attempts.' . md5($email);
        $attempts = $session->get($attemptKey, []);
        
        // Remove old attempts
        $now = time();
        $attempts = array_filter($attempts, function($timestamp) use ($now) {
            return $timestamp > ($now - self::ATTEMPT_INTERVAL);
        });
        
        // Check if too many attempts
        if (count($attempts) >= self::MAX_ATTEMPTS) {
            throw new TooManyLoginAttemptsAuthenticationException('Too many failed login attempts. Please try again later.');
        }

        // Store current username for displaying in case of error
        $session->set(Security::LAST_USERNAME, $email);

        // Create a user loader using the entity manager
        $userLoader = function ($userIdentifier) {
            $userRepository = $this->entityManager->getRepository(User::class);
            $user = $userRepository->findOneBy(['email' => $userIdentifier]);
            
            if (!$user) {
                throw new \Symfony\Component\Security\Core\Exception\UserNotFoundException();
            }
            
            // Check if the user account is banned
            if ($user->getAccountStatus() === 'BANNED') {
                throw new CustomUserMessageAuthenticationException('Your account has been banned. Please contact support for assistance.');
            }
            
            // Check if the user account is suspended
            if ($user->getAccountStatus() === 'SUSPENDED') {
                throw new CustomUserMessageAuthenticationException('Your account has been temporarily suspended. Please try again later or contact support.');
            }
            
            return $user;
        };

        return new Passport(
            new UserBadge($email, $userLoader),
            new PasswordCredentials($password),
            [
                new CsrfTokenBadge('authenticate', $csrfToken),
                new RememberMeBadge(),
            ]
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $user = $token->getUser();
        $session = $request->getSession();
        
        // Clear any existing failed login attempts
        $attemptKey = '_security.login_attempts.' . md5($user->getUserIdentifier());
        $session->remove($attemptKey);
        
        // Get the target URL from the session if it exists
        $targetPath = $this->getTargetPath($session, $firewallName);
        
        // Default redirect based on user role if no target path
        if (!$targetPath) {
            if ($user->hasRole('ROLE_ADMIN')) {
                $targetPath = $this->urlGenerator->generate('admin_dashboard');
            } elseif ($user->hasRole('ROLE_DRIVER')) {
                $targetPath = $this->urlGenerator->generate('driver_dashboard');
            } else {
                $targetPath = $this->urlGenerator->generate('app_front_home');
            }
        }
        
        // Store user info in session for JavaScript access
        $session->set('user_account_status', $user->getAccountStatus());
        $session->set('user_role', $user->getRole());
        $session->set('user_id', $user->getId_user());
        $session->set('user_name', $user->getName());
        
        // Create response
        $response = new RedirectResponse($targetPath);
        
        // Add secured cookies with user data
        $secure = $request->isSecure();
        $response->headers->setCookie(new \Symfony\Component\HttpFoundation\Cookie(
            'user_account_status', 
            $user->getAccountStatus(), 
            time() + 3600, 
            '/', 
            null, 
            $secure, 
            true, 
            false, 
            'lax'
        ));
        
        $response->headers->setCookie(new \Symfony\Component\HttpFoundation\Cookie(
            'user_role', 
            $user->getRole(),
            time() + 3600, 
            '/', 
            null, 
            $secure, 
            true, 
            false, 
            'lax'
        ));

        // Handle AJAX requests
        if ($request->isXmlHttpRequest()) {
            return new JsonResponse([
                'success' => true,
                'redirectUrl' => $targetPath,
                'user_status' => $user->getAccountStatus(),
                'user_role' => $user->getRole()
            ]);
        }

        return $response;
    }

    public function onAuthenticationFailure(Request $request, \Symfony\Component\Security\Core\Exception\AuthenticationException $exception): Response
    {
        $session = $request->getSession();
        $email = $session->get(Security::LAST_USERNAME, '');
        
        // Record failed attempt
        if (!empty($email)) {
            $attemptKey = '_security.login_attempts.' . md5($email);
            $attempts = $session->get($attemptKey, []);
            $attempts[] = time();
            $session->set($attemptKey, $attempts);
        }
        
        // Get detailed exception information
        $errorCode = 'authentication_error';
        $field = null;
        
        // Map specific security exceptions to user-friendly messages with more detail
        if ($exception instanceof \Symfony\Component\Security\Core\Exception\BadCredentialsException) {
            // Check if the user exists to determine if it's a password issue or non-existent account
            $userExists = false;
            if (!empty($email)) {
                $userRepository = $this->entityManager->getRepository(User::class);
                $user = $userRepository->findOneBy(['email' => $email]);
                $userExists = ($user !== null);
            }
            
            if ($userExists) {
                $errorMessage = 'The password you entered is incorrect. Please try again.';
                $errorCode = 'invalid_password';
                $field = 'password';
            } else {
                $errorMessage = 'No account found with this email address. Please check your email or create a new account.';
                $errorCode = 'email_not_found';
                $field = 'email';
            }
        } elseif ($exception instanceof \Symfony\Component\Security\Core\Exception\UserNotFoundException) {
            $errorMessage = 'No account exists with this email address. Please check your spelling or create a new account.';
            $errorCode = 'user_not_found';
            $field = 'email';
        } elseif ($exception instanceof TooManyLoginAttemptsAuthenticationException) {
            $errorMessage = 'Too many failed login attempts. For security reasons, please wait at least one hour before trying again.';
            $errorCode = 'too_many_attempts';
        } elseif ($exception instanceof \Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException) {
            $errorMessage = $exception->getMessage();
            
            // Set the field based on the error message
            if (strpos(strtolower($errorMessage), 'email') !== false) {
                $field = 'email';
                if (strpos(strtolower($errorMessage), 'valid') !== false) {
                    $errorCode = 'invalid_email_format';
                }
            } elseif (strpos(strtolower($errorMessage), 'password') !== false) {
                $field = 'password';
            }
        } else {
            $errorMessage = 'Authentication failed. Please check your credentials and try again.';
        }

        // Handle AJAX requests with more detailed response
        if ($request->isXmlHttpRequest()) {
            return new JsonResponse([
                'success' => false,
                'message' => $errorMessage,
                'error_code' => $errorCode,
                'field' => $field
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Store error for standard form submission
        $session->set(Security::AUTHENTICATION_ERROR, new CustomUserMessageAuthenticationException($errorMessage));
        return new RedirectResponse($this->getLoginUrl($request));
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }
}