<?php

namespace App\Service;

use App\Entity\User;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Cookie;
use Scheb\TwoFactorBundle\Model\Totp\TotpConfiguration;
use Scheb\TwoFactorBundle\Model\Totp\TotpConfigurationInterface;

class TwoFactorSessionHandler
{    private $requestStack;
    private const COOKIE_2FA_ENABLED = 'wamiagp_2fa_enabled';
    private const COOKIE_2FA_SECRET = 'wamiagp_2fa_secret';
    private const COOKIE_2FA_BACKUP_CODES = 'wamiagp_2fa_backup';
    
    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }
    
    /**
     * Check if 2FA is enabled for the user (in session and cookie)
     */
    public function is2faEnabled(): bool
    {
        $session = $this->requestStack->getSession();
        $request = $this->requestStack->getCurrentRequest();
        
        // First check if we've explicitly set 2FA as enabled in the session during login
        if ($session->get('2fa_enabled', false)) {
            // Check if we also have a secret (required for 2FA to work)
            if ($session->has('permanent_totp_secret') || $session->has('totp_secret')) {
                return true;
            }
            
            // We have the flag but no secret, check cookies
            if ($request && $request->cookies->has(self::COOKIE_2FA_SECRET)) {
                $secret = $request->cookies->get(self::COOKIE_2FA_SECRET);
                $decodedSecret = base64_decode($secret);
                
                // If we have a valid secret in cookie, store it in session and return true
                if ($decodedSecret) {
                    $session->set('permanent_totp_secret', $decodedSecret);
                    return true;
                }
            }
        }
        
        // Then check cookie if 2FA flag not in session
        if ($request && $request->cookies->has(self::COOKIE_2FA_ENABLED)) {
            $cookieValue = $request->cookies->get(self::COOKIE_2FA_ENABLED);
            if ($cookieValue === 'true' || $cookieValue === '1') {
                // Verify that we also have a secret
                if ($request->cookies->has(self::COOKIE_2FA_SECRET)) {
                    $secret = $request->cookies->get(self::COOKIE_2FA_SECRET);
                    $decodedSecret = base64_decode($secret);
                    
                    if ($decodedSecret) {
                        // Update session from cookie
                        $session->set('2fa_enabled', true);
                        $session->set('permanent_totp_secret', $decodedSecret);
                        return true;
                    }
                }
            }
        }
        
        return false;
    }
    
    /**
     * Get the TOTP secret from session or cookie
     */
    public function getTotpSecret(): ?string
    {
        $session = $this->requestStack->getSession();
        $secret = $session->get('permanent_totp_secret');
        
        // If we have a valid secret in session, return it
        if ($secret && $this->isValidSecretFormat($secret)) {
            error_log('Found valid TOTP secret in session');
            return $secret;
        }
        
        // Try to get from temporary session key
        $tempSecret = $session->get('totp_secret');
        if ($tempSecret && $this->isValidSecretFormat($tempSecret)) {
            error_log('Found valid temporary TOTP secret in session');
            return $tempSecret;
        }
        
        // Try to get from cookie
        $request = $this->requestStack->getCurrentRequest();
        if ($request && $request->cookies->has(self::COOKIE_2FA_SECRET)) {
            $cookieSecret = $request->cookies->get(self::COOKIE_2FA_SECRET);
            
            try {
                $decodedSecret = base64_decode($cookieSecret);
                
                // Check if it's a valid base32 secret
                if ($this->isValidSecretFormat($decodedSecret)) {
                    error_log('Found valid TOTP secret in cookie');
                    $session->set('permanent_totp_secret', $decodedSecret);
                    return $decodedSecret;
                } else {
                    error_log('Invalid TOTP secret format in cookie: ' . substr($cookieSecret, 0, 5) . '...');
                }
            } catch (\Exception $e) {
                error_log('Error decoding secret from cookie: ' . $e->getMessage());
            }
        }
        
        error_log('No valid TOTP secret found anywhere');
        return null;
    }
    
    /**
     * Check if a string is a valid TOTP secret (Base32 format)
     */
    private function isValidSecretFormat(?string $secret): bool
    {
        if (empty($secret)) {
            return false;
        }
        
        // Base32 characters are A-Z and 2-7
        return preg_match('/^[A-Z2-7]+$/', $secret) === 1;
    }
    
    /**
     * Set the TOTP secret in session during setup
     */
    public function setTemporaryTotpSecret(?string $secret = null): string
    {
        $session = $this->requestStack->getSession();
        
        if (!$secret) {
            $secret = $this->generateTotpSecret();
        }
        
        $session->set('totp_secret', $secret);
        return $secret;
    }
    
    /**
     * Get the temporary TOTP secret from session
     */
    public function getTemporaryTotpSecret(): ?string
    {
        $session = $this->requestStack->getSession();
        return $session->get('totp_secret');
    }
    
    /**
     * Confirm and store the permanent TOTP secret in session and cookie
     */
    public function confirmTotpSecret(): array
    {
        $session = $this->requestStack->getSession();
        $secret = $session->get('totp_secret');
        $cookies = [];
        
        if ($secret) {
            $session->set('permanent_totp_secret', $secret);
            $session->set('2fa_enabled', true);
            $session->remove('totp_secret'); // Remove temporary secret
            
            // Create secure cookies for persistence
            $secretCookie = new Cookie(
                self::COOKIE_2FA_SECRET,
                base64_encode($secret),
                time() + (86400 * 30), // 30 days
                '/',
                null,
                true, // secure
                true  // httpOnly
            );
            
            $enabledCookie = new Cookie(
                self::COOKIE_2FA_ENABLED,
                'true',
                time() + (86400 * 30), // 30 days
                '/',
                null,
                true, // secure
                true  // httpOnly
            );
            
            $cookies[] = $secretCookie;
            $cookies[] = $enabledCookie;
        }
        
        return $cookies;
    }
    
    /**
     * Disable 2FA and remove cookies
     */
    public function disable2fa(): array
    {
        $session = $this->requestStack->getSession();
        $session->remove('2fa_enabled');
        $session->remove('totp_secret');
        $session->remove('permanent_totp_secret');
        
        // Create expired cookies to remove them
        $secretCookie = new Cookie(
            self::COOKIE_2FA_SECRET,
            '',
            time() - 3600,
            '/',
            null,
            true,
            true
        );
        
        $enabledCookie = new Cookie(
            self::COOKIE_2FA_ENABLED,
            '',
            time() - 3600,
            '/',
            null,
            true,
            true
        );
        
        return [$secretCookie, $enabledCookie];
    }
    
    /**
     * Get TOTP configuration for the user
     */
    public function getTotpConfiguration(): ?TotpConfigurationInterface
    {
        $secret = $this->getTotpSecret();
        
        if (!$secret) {
            return null;
        }
        
        // Use our custom TOTP configuration class
        return new \App\Security\TwoFactor\TotpConfiguration($secret);
    }
      /**
     * Get detailed 2FA status information
     */
    public function get2faStatusInfo(): array
    {
        $session = $this->requestStack->getSession();
        $request = $this->requestStack->getCurrentRequest();
        
        $enabled = $this->is2faEnabled();
        $secret = $this->getTotpSecret();
        $temporarySecret = $this->getTemporaryTotpSecret();
        
        // Check if this is a trusted device
        $isTrustedDevice = false;
        if ($request) {
            $isTrustedDevice = $request->cookies->has('wamiagp_2fa_trusted');
        }
        
        return [
            'enabled' => $enabled,
            'hasSecret' => $secret !== null,
            'setupInProgress' => $temporarySecret !== null,
            'isTrustedDevice' => $isTrustedDevice,
            'hasBackupCodes' => $this->hasBackupCodes()
        ];
    }
    
    /**
     * Generate backup codes for 2FA recovery
     */
    public function generateBackupCodes(): array
    {
        $codes = [];
        for ($i = 0; $i < 8; $i++) {
            $codes[] = $this->generateRandomCode();
        }
        
        // Store hashed codes in session/cookie
        $session = $this->requestStack->getSession();
        $hashedCodes = array_map(function($code) {
            return password_hash($code, PASSWORD_BCRYPT);
        }, $codes);
        
        $session->set('backup_codes', $hashedCodes);
        
        // Create backup codes cookie
        $backupCookie = new Cookie(
            self::COOKIE_2FA_BACKUP_CODES,
            base64_encode(json_encode($hashedCodes)),
            time() + (86400 * 30), // 30 days
            '/',
            null,
            true, // secure
            true  // httpOnly
        );
        
        return [
            'codes' => $codes,
            'cookie' => $backupCookie
        ];
    }
    
    /**
     * Check if user has backup codes
     */
    private function hasBackupCodes(): bool
    {
        $session = $this->requestStack->getSession();
        $request = $this->requestStack->getCurrentRequest();
        
        if ($session->has('backup_codes')) {
            return true;
        }
        
        if ($request && $request->cookies->has(self::COOKIE_2FA_BACKUP_CODES)) {
            // Load from cookie
            $encodedCodes = $request->cookies->get(self::COOKIE_2FA_BACKUP_CODES);
            $hashedCodes = json_decode(base64_decode($encodedCodes), true);
            if ($hashedCodes && is_array($hashedCodes)) {
                $session->set('backup_codes', $hashedCodes);
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Verify a backup code
     */
    public function verifyBackupCode(string $code): bool
    {
        $session = $this->requestStack->getSession();
        $hashedCodes = $session->get('backup_codes', []);
        
        foreach ($hashedCodes as $index => $hashedCode) {
            if (password_verify($code, $hashedCode)) {
                // Remove used code
                unset($hashedCodes[$index]);
                $session->set('backup_codes', $hashedCodes);
                
                // Update cookie
                $backupCookie = new Cookie(
                    self::COOKIE_2FA_BACKUP_CODES,
                    base64_encode(json_encode($hashedCodes)),
                    time() + (86400 * 30), // 30 days
                    '/',
                    null,
                    true, // secure
                    true  // httpOnly
                );
                
                return true;
            }
        }
        
        return false;
    }
      /**
     * Generate a random TOTP secret
     */
    public function generateTotpSecret(): string
    {
        // Generate a 16-character base32 secret
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = '';
        
        for ($i = 0; $i < 16; $i++) {
            $secret .= $characters[random_int(0, strlen($characters) - 1)];
        }
        
        return $secret;
    }
    
    /**
     * Generate a random backup code
     */
    private function generateRandomCode(): string
    {
        // Generate an 8-character code with letters and numbers
        $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $code = '';
        
        for ($i = 0; $i < 8; $i++) {
            $code .= $characters[random_int(0, strlen($characters) - 1)];
        }
        
        // Format as XXXX-XXXX for readability
        return substr($code, 0, 4) . '-' . substr($code, 4, 4);
    }
    
    /**
     * Enable 2FA for the user
     */
    public function enableTwoFactorAuth(string $secret): bool
    {
        try {
            $session = $this->requestStack->getSession();
            $response = $this->requestStack->getCurrentRequest()->attributes->get('_controller_result');
            
            // Validate that the secret is present
            if (empty($secret)) {
                return false;
            }
            
            // Store in session
            $session->set('2fa_enabled', true);
            $session->set('2fa_secret', $secret);
            
            // Create cookies for persistent storage (30 days)
            $cookieLifetime = 3600 * 24 * 30;
            
            // Set the 2FA enabled cookie
            $cookie = new Cookie(
                self::COOKIE_2FA_ENABLED,
                '1',
                time() + $cookieLifetime,
                '/',
                null,
                false,
                true
            );
            
            // Set the secret cookie (encrypted)
            $secretCookie = new Cookie(
                self::COOKIE_2FA_SECRET,
                $this->encryptSecret($secret),
                time() + $cookieLifetime,
                '/',
                null,
                false,
                true
            );
            
            // Add cookies to response if we have one
            if ($response instanceof Response) {
                $response->headers->setCookie($cookie);
                $response->headers->setCookie($secretCookie);
            }
            
            return true;
        } catch (\Exception $e) {
            // Log the error
            error_log('Error enabling 2FA: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Simple encryption for the secret (for demo purposes)
     * In production, use a proper encryption library
     */
    private function encryptSecret(string $secret): string
    {
        return base64_encode($secret);
    }
    
    /**
     * Simple decryption for the secret
     */
    private function decryptSecret(string $encryptedSecret): string
    {
        return base64_decode($encryptedSecret);
    }

    /**
     * Check if a TOTP secret is available in the session
     */
    public function hasTotpSecret(): bool
    {
        // First, check for a secret in the session or cookie
        $secret = $this->getTotpSecret();
        
        // Check if we have a secret and it's a valid Base32 string
        if ($secret && preg_match('/^[A-Z2-7]+$/', $secret)) {
            error_log('Found valid TOTP secret for user in session/cookie');
            return true;
        }
        
        // Next, try to get the current user and check if they have a stored otpSecret
        try {
            $request = $this->requestStack->getCurrentRequest();
            if (!$request) {
                return false;
            }
            
            $token = $request->getSession()->get('_security_main');
            if (!$token) {
                return false;
            }
            
            $user = unserialize($token)->getUser();
            if (!$user || !method_exists($user, 'getOtpSecret')) {
                return false;
            }
            
            $otpSecret = $user->getOtpSecret();
            if ($otpSecret && preg_match('/^[A-Z2-7]+$/', $otpSecret)) {
                error_log('Found valid TOTP secret in user database record: ' . substr($otpSecret, 0, 5) . '...');
                
                // Store it in session for future use
                $request->getSession()->set('permanent_totp_secret', $otpSecret);
                return true;
            }
            
            // If we get here, the user doesn't have a valid TOTP secret
            // Clear any 2FA flags to prevent redirect loops
            $session = $request->getSession();
            $session->remove('2fa_enabled');
            $session->remove('totp_secret');
            $session->remove('permanent_totp_secret');
            $session->remove('2fa_in_progress');
            error_log('No valid TOTP secret found, cleared 2FA session data');
        } catch (\Exception $e) {
            error_log('Error checking user entity for TOTP secret: ' . $e->getMessage());
        }
        
        error_log('No valid TOTP secret found anywhere');
        return false;
    }
}
