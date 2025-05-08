<?php

namespace App\Security;

use App\Entity\User;
use App\Service\TwoFactorSessionHandler;
use Scheb\TwoFactorBundle\Security\TwoFactor\Condition\TwoFactorConditionInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\AuthenticationContextInterface;

class TwoFactorCondition implements TwoFactorConditionInterface
{
    private $twoFactorSessionHandler;
    
    public function __construct(TwoFactorSessionHandler $twoFactorSessionHandler)
    {
        $this->twoFactorSessionHandler = $twoFactorSessionHandler;
    }
    
    public function shouldPerformTwoFactorAuthentication(AuthenticationContextInterface $context): bool
    {
        $token = $context->getToken();
        $user = $token->getUser();
        $request = $context->getRequest();
        $session = $request->getSession();
        
        // Debug - log that we're checking 2FA condition
        error_log('TwoFactorCondition: Checking if 2FA should be performed for user: ' . ($user instanceof User ? $user->getEmail() : 'unknown'));
        
        // Only apply 2FA for User entities
        if (!$user instanceof User) {
            error_log('TwoFactorCondition: Not a User entity, skipping 2FA');
            return false;
        }
        
        // Check if we're already in the 2FA process to avoid loops
        $tokenClass = get_class($token);
        if (strpos($tokenClass, 'TwoFactorToken') !== false) {
            error_log('TwoFactorCondition: Already using a TwoFactorToken, not triggering 2FA again');
            return false;
        }
        
        // Check if this is a 2FA-exempt route
        if ($request) {
            $currentPath = $request->getPathInfo();
            
            // List of paths that should not trigger 2FA (whitelist approach)
            $exemptPaths = [
                '/2fa',                    // 2FA login page
                '/login/check',            // Login check path 
                '/login',                  // Login page
                '/login/face',             // Face login page
                '/login/face/verify',      // Face verification endpoint
                '/logout',                 // Logout path
                '/profile/2fa-setup',      // 2FA setup page
                '/profile/2fa-verify',     // 2FA verification endpoint
                '/profile/2fa-qr-code',    // 2FA QR code generation
                '/profile/2fa-disable',    // 2FA disable endpoint
                '/profile/2fa-backup-codes',// 2FA backup codes
                '/check-2fa-status',       // 2FA status check endpoint
                '/verify-email',           // Email verification
                '/reset-password',         // Password reset
                '/api/',                   // API endpoints
                '/health-check',           // Health check endpoint
            ];
            
            // Check if current path starts with any of the exempt paths
            foreach ($exemptPaths as $exemptPath) {
                if (strpos($currentPath, $exemptPath) === 0) {
                    error_log('TwoFactorCondition: Path ' . $currentPath . ' is exempt from 2FA');
                    return false;
                }
            }
        }
        
        // IMPORTANT: Check if we're already in the 2FA process to break potential loops
        if ($session->has('2fa_in_progress') && $session->get('2fa_in_progress') === true) {
            error_log('TwoFactorCondition: 2FA already in progress, not triggering again');
            
            // Check if we're exceeding redirect attempts
            $accessCount = $session->get('2fa_page_access_count', 0);
            if ($accessCount > 3) {
                error_log('TwoFactorCondition: Too many 2FA redirects, disabling 2FA flow temporarily');
                $session->set('2fa_in_progress', false);
                $session->remove('2fa_page_access_count');
                return false;
            }
            
            // If 2FA is in progress but we're not on a 2FA path, we need to force redirect to 2FA
            if ($request && strpos($currentPath, '/2fa') !== 0) {
                error_log('TwoFactorCondition: Not on 2FA path but 2FA is in progress. Will redirect to 2FA.');
                $session->set('2fa_page_access_count', $accessCount + 1);
                return true;
            }
            
            return false;
        }
        
        // If we don't have a valid TOTP secret, we can't verify 2FA
        // So we need to disable 2FA and redirect to setup
        $hasValidSecret = $this->twoFactorSessionHandler->hasTotpSecret();
        if ($user->isVerified() && !$hasValidSecret) {
            error_log('TwoFactorCondition: User is verified but missing TOTP secret. 2FA flow disabled.');
            
            // Clear 2FA flag in session to prevent redirect loops
            $session->set('missing_totp_secret', true);
            return false;
        }

        // Only require 2FA if the user has it enabled
        if ($user instanceof User && $user->isVerified() && $user->isTotpAuthenticationEnabled()) {
            $otpSecret = $user->getOtpSecret();
            error_log('TwoFactorCondition: User is verified and has 2FA enabled (otp_secret: ' . 
                (empty($otpSecret) ? 'empty' : 'present') . '), requiring 2FA for user: ' . $user->getEmail());
            
            // Add a session flag to indicate 2FA is in progress
            $session->set('2fa_in_progress', true);
            
            return true;
        }
        
        error_log('TwoFactorCondition: User does not have 2FA enabled, skipping 2FA');
        return false;
    }
} 