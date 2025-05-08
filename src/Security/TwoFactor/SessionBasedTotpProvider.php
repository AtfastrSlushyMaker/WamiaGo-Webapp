<?php

namespace App\Security\TwoFactor;

use App\Entity\User;
use App\Service\TwoFactorSessionHandler;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\TwoFactorProviderInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\TwoFactorFormRendererInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\AuthenticationContextInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class SessionBasedTotpProvider implements TwoFactorProviderInterface
{
    private $authenticator;
    private $sessionHandler;
    private $urlGenerator;
    private $formRenderer;

    public function __construct(
        SessionBasedTotpAuthenticator $authenticator,
        TwoFactorSessionHandler $sessionHandler,
        UrlGeneratorInterface $urlGenerator,
        TwoFactorFormRendererInterface $formRenderer
    ) {
        $this->authenticator = $authenticator;
        $this->sessionHandler = $sessionHandler;
        $this->urlGenerator = $urlGenerator;
        $this->formRenderer = $formRenderer;
    }

    /**
     * Begin the authentication process.
     */
    public function beginAuthentication(AuthenticationContextInterface $context): bool
    {
        $user = $context->getUser();
        if (!$user instanceof User) {
            return false;
        }

        // Only require 2FA for verified users who actually have a secret
        $isVerified = $user->isVerified();
        $hasSecret = $this->sessionHandler->hasTotpSecret();
        
        // Log the 2FA authentication decision
        error_log(sprintf(
            'SessionBasedTotpProvider: User %s, verified: %s, has secret: %s, requires 2FA: %s',
            $user->getEmail(),
            $isVerified ? 'yes' : 'no',
            $hasSecret ? 'yes' : 'no',
            ($isVerified && $hasSecret) ? 'yes' : 'no'
        ));
        
        return $isVerified && $hasSecret;
    }

    /**
     * Get the form renderer for this 2FA provider.
     */
    public function getFormRenderer(): TwoFactorFormRendererInterface
    {
        return $this->formRenderer;
    }

    /**
     * Validates the code provided by the user.
     */
    public function validateAuthenticationCode(object $user, string $authenticationCode): bool
    {
        if (!$user instanceof User) {
            return false;
        }

        return $this->authenticator->checkCode($user, $authenticationCode);
    }

    /**
     * Returns the provider name.
     */
    public function getProviderName(): string
    {
        return 'totp';
    }

    /**
     * Prepare authentication, can be used to set up session variables.
     */
    public function prepareAuthentication(object $user): void
    {
        // Nothing to do here for session-based TOTP as our session handling is done elsewhere
    }
} 