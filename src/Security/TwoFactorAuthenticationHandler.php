<?php

namespace App\Security;

use App\Service\TwoFactorSessionHandler;
use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorToken;
use Scheb\TwoFactorBundle\Security\Http\Authentication\AuthenticationRequiredHandlerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class TwoFactorAuthenticationHandler implements AuthenticationRequiredHandlerInterface
{
    private $urlGenerator;
    private $twoFactorSessionHandler;
    
    public function __construct(
        UrlGeneratorInterface $urlGenerator,
        TwoFactorSessionHandler $twoFactorSessionHandler
    ) {
        $this->urlGenerator = $urlGenerator;
        $this->twoFactorSessionHandler = $twoFactorSessionHandler;
    }
    
    public function onAuthenticationRequired(Request $request, TokenInterface $token): Response
    {
        // Check for special paths where we shouldn't redirect back to 2FA
        $currentPath = $request->getPathInfo();
        $bypassPaths = [
            '/login/face',
            '/login/face/verify',
            '/2fa',
            '/2fa/check',
            '/health-check',
            '/api/'
        ];
        
        foreach ($bypassPaths as $path) {
            if (strpos($currentPath, $path) === 0) {
                // Don't redirect to 2FA on these paths
                return new RedirectResponse($this->urlGenerator->generate('app_front_home'));
            }
        }
        
        // Check for too many redirects
        $session = $request->getSession();
        $redirectCount = $session->get('2fa_redirect_count', 0);
        
        if ($redirectCount > 5) {
            // Reset the counter and skip 2FA to break out of redirect loops
            $session->remove('2fa_redirect_count');
            $session->set('2fa_in_progress', false);
            return new RedirectResponse($this->urlGenerator->generate('app_front_home'));
        }
        
        // Increment the redirect counter
        $session->set('2fa_redirect_count', $redirectCount + 1);
        
        // Always redirect to 2FA if the token is a TwoFactorToken
        // The bundle only creates this token for users who need 2FA
        if ($token instanceof TwoFactorToken) {
            return new RedirectResponse($this->urlGenerator->generate('app_2fa_login'));
        }
        
        // If we somehow get here without a TwoFactorToken, redirect to profile
        return new RedirectResponse($this->urlGenerator->generate('app_profile'));
    }
}
