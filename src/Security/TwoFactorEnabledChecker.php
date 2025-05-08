<?php

namespace App\Security;

use App\Entity\User;
use App\Service\TwoFactorSessionHandler;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Totp\TotpAuthenticatorInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class TwoFactorEnabledChecker implements TotpAuthenticatorInterface
{
    private $twoFactorSessionHandler;
    
    public function __construct(TwoFactorSessionHandler $twoFactorSessionHandler)
    {
        $this->twoFactorSessionHandler = $twoFactorSessionHandler;
    }
    
    /**
     * Determine if 2FA process should begin for the user
     */
    public function beginAuthentication($user): bool
    {
        if (!$user instanceof User) {
            return false;
        }
        
        // Check if the user has verified their account AND has a TOTP secret
        if ($user->isVerified() && $this->twoFactorSessionHandler->hasTotpSecret()) {
            error_log('2FA required for user: ' . $user->getEmail() . ' (verified and has TOTP secret)');
            return true;
        }
        
        // If user is verified but doesn't have a TOTP secret, they need to set up 2FA first
        if ($user->isVerified() && !$this->twoFactorSessionHandler->hasTotpSecret()) {
            error_log('User ' . $user->getEmail() . ' is verified but has no TOTP secret. Needs setup.');
            return false;
        }
        
        error_log('2FA not required for user: ' . $user->getEmail() . ' (not verified)');
        return false;
    }
    
    /**
     * This method is needed to implement the TotpAuthenticatorInterface
     */
    public function checkCode($user, string $code): bool
    {
        if (!$user instanceof User) {
            return false;
        }
        
        // Get the secret from session or cookie
        $secret = $this->twoFactorSessionHandler->getTotpSecret();
        
        if (!$secret) {
            return false;
        }
        
        try {
            // Use TOTP library to validate code with the correct digits
            $totp = \OTPHP\TOTP::create($secret);
            $totp->setDigits(6);
            $totp->setPeriod(30);
            
            // Allow some leeway (one period before/after)
            return $totp->verify($code, null, 1);
        } catch (\Exception $e) {
            error_log('Error verifying TOTP code: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * This method is needed to implement the TotpAuthenticatorInterface
     */
    public function getQRContent($user): string
    {
        if (!$user instanceof User) {
            return "";
        }
        
        $secret = $this->twoFactorSessionHandler->getTotpSecret() ?? 
                 $this->twoFactorSessionHandler->getTemporaryTotpSecret();
                
        if (!$secret) {
            return "";
        }
        
        $totp = \OTPHP\TOTP::create($secret);
        $totp->setLabel($user->getEmail());
        $totp->setIssuer('WamiaGo');
        
        return $totp->getProvisioningUri();
    }
    
    /**
     * Generate a new TOTP secret
     */
    public function generateSecret(): string
    {
        // Generate a random 16 character secret using Base32 encoding
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = '';
        $length = 16;
        
        for ($i = 0; $i < $length; $i++) {
            $secret .= $chars[random_int(0, strlen($chars) - 1)];
        }
        
        return $secret;
    }
} 