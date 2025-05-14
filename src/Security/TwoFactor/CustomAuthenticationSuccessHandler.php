<?php

namespace App\Security\TwoFactor;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class CustomAuthenticationSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    use TargetPathTrait;
    
    private $urlGenerator;

    public function __construct(UrlGeneratorInterface $urlGenerator)
    {
        $this->urlGenerator = $urlGenerator;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token): Response
    {
        // Log the successful authentication
        error_log('2FA authentication success for user: ' . $token->getUserIdentifier());
        
        // Clear any 2FA-related session variables to prevent future issues
        $session = $request->getSession();
        $session->remove('2fa_enabled');
        $session->remove('totp_secret');
        $session->remove('2fa_in_progress'); // Clear the flag we set in TwoFactorCondition
        
        // Set a flag indicating 2FA was completed successfully
        $session->set('2fa_completed', true);
        
        // Check if there was a target path the user was trying to access before authentication
        $firewallName = 'main';
        if ($targetPath = $this->getTargetPath($session, $firewallName)) {
            error_log("Redirecting to original target: $targetPath");
            $session->remove('_security.main.target_path');
            return new RedirectResponse($targetPath);
        }
        
        // Check if user is admin and redirect to dashboard
        $user = $token->getUser();
        if (method_exists($user, 'getRole')) {
            $role = $user->getRole();
            $roleValue = is_object($role) && method_exists($role, 'value') ? $role->value : $role;
            
            if ($roleValue === 'ADMIN') {
                return new RedirectResponse($this->urlGenerator->generate('admin_dashboard'));
            }
        }
        
        // Default to homepage if no target path was stored
        return new RedirectResponse($this->urlGenerator->generate('app_front_home'));
    }
} 