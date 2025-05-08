<?php

namespace App\Security\TwoFactor;

use App\Entity\User;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Totp\TotpAuthenticatorInterface;
use Scheb\TwoFactorBundle\Model\Totp\TwoFactorInterface;

class TemporaryTotpAuthenticator implements TotpAuthenticatorInterface
{
    /**
     * Generate a new TOTP secret - placeholder implementation
     */
    public function generateSecret(): string
    {
        // Return a placeholder secret
        return 'TEMPORARY_DISABLED_SECRET';
    }
    
    /**
     * Check if the provided code is valid - placeholder implementation
     */
    public function checkCode(TwoFactorInterface $user, string $code): bool
    {
        // Always return false since 2FA is disabled
        return false;
    }
    
    /**
     * Get the QR code content for the TOTP setup - placeholder implementation
     */
    public function getQRContent(TwoFactorInterface $user): string
    {
        // Return placeholder content
        return 'otpauth://totp/WamiaGo:user@example.com?secret=TEMPORARY_DISABLED_SECRET&issuer=WamiaGo&digits=6';
    }
} 