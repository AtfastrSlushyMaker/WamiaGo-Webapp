<?php

namespace App\Security;

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

class LoginAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    public const LOGIN_ROUTE = 'app_login';

    public function __construct(private UrlGeneratorInterface $urlGenerator)
    {
    }

    public function authenticate(Request $request): Passport
    {
        $email = $request->request->get('email', '');
        $password = $request->request->get('password', '');

        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new CustomUserMessageAuthenticationException('Please enter a valid email address.');
        }

        // Check for empty fields
        if (empty($email)) {
            throw new CustomUserMessageAuthenticationException('Email cannot be empty.');
        }

        if (empty($password)) {
            throw new CustomUserMessageAuthenticationException('Password cannot be empty.');
        }

        $request->getSession()->set(Security::LAST_USERNAME, $email);

        return new Passport(
            new UserBadge($email),
            new PasswordCredentials($password),
            [
                new CsrfTokenBadge('authenticate', $request->request->get('_csrf_token')),
                new RememberMeBadge(),
            ]
        );
    }
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $user = $token->getUser();
        $redirectUrl = $this->urlGenerator->generate('app_front_home');

        if ($user->hasRole('ROLE_ADMIN')) {
            $redirectUrl = $this->urlGenerator->generate('admin_dashboard');
        } elseif ($user->hasRole('ROLE_DRIVER')) {
            $redirectUrl = $this->urlGenerator->generate('driver_dashboard');
        }

        if ($request->isXmlHttpRequest()) {
            return new JsonResponse([
                'success' => true,
                'redirectUrl' => $redirectUrl
            ]);
        }

        return new RedirectResponse($redirectUrl);
    }
    public function onAuthenticationFailure(Request $request, \Symfony\Component\Security\Core\Exception\AuthenticationException $exception): Response
    {
        $errorMessage = 'Invalid credentials. Please check your email and password.';
        
        // Map specific security exceptions to user-friendly messages
        if ($exception instanceof \Symfony\Component\Security\Core\Exception\BadCredentialsException) {
            $errorMessage = 'The password you entered is incorrect.';
        } elseif ($exception instanceof \Symfony\Component\Security\Core\Exception\UserNotFoundException) {
            $errorMessage = 'No account found with this email address.';
        } elseif ($exception instanceof \Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException) {
            $errorMessage = $exception->getMessage();
        }

        if ($request->isXmlHttpRequest()) {
            return new JsonResponse([
                'success' => false,
                'message' => $errorMessage
            ], Response::HTTP_UNAUTHORIZED);
        }

        $request->getSession()->set(Security::AUTHENTICATION_ERROR, new \Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException($errorMessage));
        return new RedirectResponse($this->getLoginUrl($request));
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }
}