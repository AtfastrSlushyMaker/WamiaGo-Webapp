<?php

namespace App\Security\TwoFactor;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;

class CustomAuthenticationFailureHandler implements AuthenticationFailureHandlerInterface
{
    private $urlGenerator;

    public function __construct(UrlGeneratorInterface $urlGenerator)
    {
        $this->urlGenerator = $urlGenerator;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        // Log the authentication failure
        error_log('2FA authentication failure: ' . $exception->getMessage());
        
        // Add a flash message with the error
        $request->getSession()->getFlashBag()->add('error', 'Invalid authentication code');
        
        // Redirect back to the 2FA form
        return new RedirectResponse($this->urlGenerator->generate('app_2fa_login'));
    }
} 