<?php

namespace App\EventListener;

use App\Service\TwoFactorSessionHandler;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;
use App\Entity\User;

class LoginSuccessListener implements EventSubscriberInterface
{
    private $requestStack;
    private $twoFactorSessionHandler;
    
    public function __construct(
        RequestStack $requestStack,
        TwoFactorSessionHandler $twoFactorSessionHandler
    ) {
        $this->requestStack = $requestStack;
        $this->twoFactorSessionHandler = $twoFactorSessionHandler;
    }
    
    public static function getSubscribedEvents(): array
    {
        return [
            LoginSuccessEvent::class => 'onLoginSuccess',
        ];
    }
    
    public function onLoginSuccess(LoginSuccessEvent $event): void
    {
        $user = $event->getUser();
        
        // Skip if no user
        if (!$user) {
            return;
        }
        
        error_log('LoginSuccessListener: Login success for user: ' . (method_exists($user, 'getEmail') ? $user->getEmail() : 'unknown'));
        
        // Only enable 2FA for verified users
        if ($user instanceof User && $user->isVerified()) {
            $session = $this->requestStack->getSession();
            if ($session) {
                error_log('LoginSuccessListener: Setting 2fa_enabled flag in session for verified user');
                
                // Set the session flag that enables 2FA
                $session->set('2fa_enabled', true);
                
                // DO NOT generate a new secret here - this was causing the redirect loop
                // The secret should only be generated during setup, or retrieved from storage
                // during login for users who have already set up 2FA
                
                // Also set X-header for potential frontend use
                $response = $event->getResponse();
                if ($response) {
                    $response->headers->set('X-2FA-Enabled', 'true');
                }
            }
        } else {
            error_log('LoginSuccessListener: User is not verified or not a User entity, skipping 2FA');
        }
    }
}
