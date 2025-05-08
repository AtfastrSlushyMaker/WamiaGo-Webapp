<?php

namespace App\Security\TwoFactor;

use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Totp\TotpFactory as BaseTotpFactory;

class TotpFactory extends BaseTotpFactory
{
    public function __construct(string $server = 'WamiaGo', string $issuer = 'WamiaGo')
    {
        parent::__construct($server, $issuer);
    }
} 