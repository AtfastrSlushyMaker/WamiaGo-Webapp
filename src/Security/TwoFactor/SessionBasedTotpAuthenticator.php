<?php

namespace App\Security\TwoFactor;

use App\Entity\User;
use App\Service\TwoFactorSessionHandler;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Totp\TotpAuthenticatorInterface;
use Scheb\TwoFactorBundle\Model\Totp\TwoFactorInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Totp\TotpFactory;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class SessionBasedTotpAuthenticator implements TotpAuthenticatorInterface
{
    private $totpFactory;
    private $sessionHandler;
    private $parameterBag;
    
    public function __construct(
        TotpFactory $totpFactory, 
        TwoFactorSessionHandler $sessionHandler,
        ParameterBagInterface $parameterBag
    ) {
        $this->totpFactory = $totpFactory;
        $this->sessionHandler = $sessionHandler;
        $this->parameterBag = $parameterBag;
    }
    
    /**
     * Generate a new TOTP secret
     */
    public function generateSecret(): string
    {
        return $this->totpFactory->generateSecret();
    }
    
    /**
     * Check if the provided code is valid
     */
    public function checkCode(TwoFactorInterface $user, string $code): bool
    {
        // Get the secret from session
        $secret = $this->sessionHandler->getTotpSecret();
        
        if (!$secret) {
            return false;
        }
        
        try {
            // Create TOTP with our parameters
            $totp = \OTPHP\TOTP::create($secret);
            $totp->setDigits(6); // This is the important parameter that was missing
            $totp->setPeriod(30);
            
            // Allow some leeway for time drift (one period before/after)
            return $totp->verify($code, null, 1);
        } catch (\Exception $e) {
            error_log('Error checking TOTP code: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get the QR code content for the TOTP setup
     */
    public function getQRContent(TwoFactorInterface $user): string
    {
        if (!$user instanceof User) {
            throw new \InvalidArgumentException('User must be an instance of User');
        }
        
        $username = $user->getTotpAuthenticationUsername();
        $secret = $this->sessionHandler->getTemporaryTotpSecret();
        
        if (!$secret) {
            throw new \LogicException('No TOTP secret found in session');
        }
        
        $serverName = $this->parameterBag->get('scheb_two_factor.totp.server_name');
        $issuer = $this->parameterBag->get('scheb_two_factor.totp.issuer');
        $digits = 6; // Hardcoded value instead of trying to fetch the parameter
        
        return sprintf(
            'otpauth://totp/%s:%s?secret=%s&issuer=%s&digits=%d',
            urlencode($issuer ?? $serverName),
            urlencode($username),
            $secret,
            urlencode($issuer ?? $serverName),
            $digits
        );
    }
}
