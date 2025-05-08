<?php

namespace App\Security;

use App\Entity\User;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Totp\TotpConfiguration;

class TotpAuthenticationConfiguration implements TotpConfiguration
{
    public function getAlgorithm(): string
    {
        return 'sha1'; // Standard algorithm used by most TOTP apps
    }

    public function getPeriod(): int
    {
        return 30; // 30-second period is standard
    }

    public function getDigits(): int
    {
        return 6; // 6 digits is standard
    }

    public function getSecret(object $user): string
    {
        if (!$user instanceof User) {
            return '';
        }

        // Return the TOTP secret from the user entity
        $secret = $user->getTotpSecret();
        
        return $secret ?? '';
    }
} 