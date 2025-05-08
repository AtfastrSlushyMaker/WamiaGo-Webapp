<?php

namespace App\Security\TwoFactor;

use Scheb\TwoFactorBundle\Model\Totp\TotpConfigurationInterface;

class TotpConfiguration implements TotpConfigurationInterface
{
    private string $secret;

    public function __construct(string $secret)
    {
        $this->secret = $secret;
    }

    public function getSecret(): string
    {
        return $this->secret;
    }

    public function getAlgorithm(): string
    {
        return 'sha1';
    }

    public function getPeriod(): int
    {
        return 30;
    }

    public function getDigits(): int
    {
        return 6;
    }
} 