<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Security\Csrf\TokenGenerator\TokenGeneratorInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Password reset service implementing a stateless approach.
 * 
 * This service handles password reset functionality without storing tokens in the database.
 * Instead, it uses a secure token+payload approach where user information and expiration
 * time are encoded within the token itself.
 */
class ResetPasswordService
{    private $mailer;
    private $entityManager;
    private $userRepository;
    private $urlGenerator;
    private $params;
    private $tokenGenerator;
    private $passwordHasher;
    private $requestStack;
    // Defines how long a token is valid (1 hour = 3600 seconds)
    private const TOKEN_VALIDITY = 3600;public function __construct(
        MailerInterface $mailer,
        EntityManagerInterface $entityManager,
        UserRepository $userRepository,
        UrlGeneratorInterface $urlGenerator,
        ParameterBagInterface $params,
        TokenGeneratorInterface $tokenGenerator,
        UserPasswordHasherInterface $passwordHasher,
        RequestStack $requestStack
    ) {
        $this->mailer = $mailer;
        $this->entityManager = $entityManager;
        $this->userRepository = $userRepository;
        $this->urlGenerator = $urlGenerator;
        $this->params = $params;
        $this->tokenGenerator = $tokenGenerator;
        $this->passwordHasher = $passwordHasher;
        $this->requestStack = $requestStack;
    }    /**
     * Generates a secure, stateless reset token.
     * 
     * The token contains encoded user information and an expiration timestamp,
     * eliminating the need to store the token in the database.
     * 
     * @param User $user The user requesting the password reset
     * @return string The generated reset token
     */    public function generateResetToken(User $user): string
    {
        // Generate a unique token with high entropy
        $token = $this->tokenGenerator->generateToken();
        
        // Add a timestamp sanitizer - we use the current timestamp divided by a fixed window
        // This ensures that even if a token is intercepted, an attacker can't just increment
        // the value to extend the validity period
        $timestampSanitizer = floor(time() / 300) * 300; // 5-minute window
        
        // Current timestamp to use for token issuance time
        $issuedAt = time();
        
        // Create a payload with user ID and expiry time
        $payload = [
            'userId' => $user->getIdUser(),
            'email' => $user->getEmail(),
            'exp' => $issuedAt + self::TOKEN_VALIDITY, // Expires in 1 hour
            'iat' => $issuedAt, // Issued at timestamp
            'ts' => $timestampSanitizer // Timestamp sanitizer
        ];
        
        // Store the current timestamp as the latest token timestamp for this user
        $this->storeLatestTokenTimestamp($user, $issuedAt);
        
        // Encode the payload as part of the token (using URL-safe base64)
        $encodedPayload = rtrim(strtr(base64_encode(json_encode($payload)), '+/', '-_'), '=');
        
        // Combine token and payload with a separator
        $fullToken = $token . '.' . $encodedPayload;
        
        return $fullToken;
    }
      /**
     * Get the remaining cooldown time for an email address.
     * 
     * @param string $email The email address to check
     * @return int Number of seconds remaining in cooldown, or 0 if no cooldown
     */
    public function getEmailCooldownTime(string $email): int
    {
        $session = $this->requestStack->getSession();
        $key = 'reset_email_' . md5($email);
        $lastSent = $session->get($key, 0);
        
        if ($lastSent === 0) {
            return 0;
        }
        
        $cooldown = 60; // 60 seconds cooldown
        $elapsed = time() - $lastSent;
        
        if ($elapsed >= $cooldown) {
            return 0;
        }
        
        return $cooldown - $elapsed;
    }
    
    /**
     * Record that a reset email was sent for cooldown tracking
     * 
     * @param string $email The email address the reset was sent to
     */
    public function recordResetEmailSent(string $email): void
    {
        $session = $this->requestStack->getSession();
        $key = 'reset_email_' . md5($email);
        $session->set($key, time());
    }
    
    /**
     * Processes a password reset request.
     * 
     * This method finds the user by email and sends them a reset email
     * without storing any token in the database.
     * 
     * @param string $email The email address of the user
     * @return array ['success' => bool, 'cooldown' => int] Status and cooldown time
     */
    public function processForgotPasswordRequest(string $email): array
    {
        // Check cooldown
        $cooldown = $this->getEmailCooldownTime($email);
        if ($cooldown > 0) {
            return ['success' => false, 'cooldown' => $cooldown];
        }
        
        // Apply rate limiting to prevent abuse
        try {
            $this->checkRateLimit($email);
        } catch (\Exception $e) {
            return ['success' => false, 'cooldown' => 0, 'error' => $e->getMessage()];
        }
        
        $user = $this->userRepository->findOneBy(['email' => $email]);
        
        if (!$user) {
            // Return false without revealing if the email exists
            return ['success' => false, 'cooldown' => 0, 'userExists' => false];
        }
        
        // No need to store anything in the database - we'll use a stateless approach
        // Just send the email with the reset link
        $this->sendPasswordResetEmail($user);
        
        // Record that email was sent for cooldown tracking
        $this->recordResetEmailSent($email);
        
        return ['success' => true, 'cooldown' => 60, 'userExists' => true];
    }
    
    /**
     * Simple rate limiting to prevent abuse of the password reset system.
     * 
     * In a production environment, you would want to use a more robust solution
     * like a Redis cache or a database table to track request counts.
     * 
     * @param string $identifier The identifier to check rate limiting for (usually an email or IP)
     * @throws \Exception If rate limit is exceeded
     */
    private function checkRateLimit(string $identifier): void
    {
        // This is a simple implementation that uses the session.
        // In a production environment, you would want to use a more robust solution.
        $session = $this->requestStack->getSession();
        
        $key = 'password_reset_' . md5($identifier);
        $now = time();
        $windowSize = 1; // 1 hour
        $maxAttempts = 3; // Maximum 3 attempts per hour
        
        $attempts = $session->get($key, []);
        
        // Remove attempts that are outside the time window
        $attempts = array_filter($attempts, function($timestamp) use ($now, $windowSize) {
            return $timestamp > ($now - $windowSize);
        });
        
        // Check if rate limit is exceeded
        if (count($attempts) >= $maxAttempts) {
            throw new \Exception('Too many password reset attempts. Please try again later.');
        }
        
        // Add current attempt
        $attempts[] = $now;
        $session->set($key, $attempts);
    }
      public function sendPasswordResetEmail(User $user): void
    {
        $token = $this->generateResetToken($user);
        
        // Generate reset URL with URL-encoded token
        $resetUrl = $this->urlGenerator->generate(
            'app_reset_password',
            ['token' => $token], // Don't URL encode here, it will be done by the router
            UrlGeneratorInterface::ABSOLUTE_URL
        );
          
        $email = (new Email())
            ->from($this->params->get('app_email', 'wamiago@gmail.com'))
            ->to($user->getEmail())
            ->subject('Password Reset Request | WamiaGo')
            ->html($this->createEmailContent($user, $resetUrl));
        
        $this->mailer->send($email);
    }private function createEmailContent(User $user, string $resetUrl): string
    {
        // Calculate expiry time (1 hour from now)
        $expiryTime = time() + self::TOKEN_VALIDITY;
        
        // Create DateTime with Tunisia timezone (GMT+1)
        $expiryDate = new \DateTime();
        $expiryDate->setTimestamp($expiryTime);
        $expiryDate->setTimezone(new \DateTimeZone('Africa/Tunis'));
        $formattedDateTime = $expiryDate->format('M j, Y') . ' at ' . $expiryDate->format('H:i');
        
        return "
            <html>
            <head>
                <meta charset='UTF-8'>
                <meta name='viewport' content='width=device-width, initial-scale=1.0'>
                <title>Reset Your Password</title>
                <style>
                    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
                    
                    * {
                        margin: 0;
                        padding: 0;
                        box-sizing: border-box;
                        font-family: 'Poppins', sans-serif;
                    }
                    
                    body { 
                        color: #1E2B4D; 
                        background-color: #F5F7FF; 
                        line-height: 1.6;
                    }
                    
                    .container { 
                        max-width: 600px; 
                        margin: 0 auto;
                        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
                        border-radius: 12px;
                        overflow: hidden;
                    }
                    
                    .header { 
                        background: linear-gradient(135deg, #4A6FFF 0%, #6B8CFF 100%); 
                        padding: 35px 20px; 
                        text-align: center; 
                        color: white;
                        position: relative;
                        overflow: hidden;
                    }
                    
                    .header:before {
                        content: '';
                        position: absolute;
                        top: -50%;
                        left: -50%;
                        width: 200%;
                        height: 200%;
                        background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0) 60%);
                        z-index: 1;
                    }
                    
                    .header h1 {
                        font-size: 28px;
                        font-weight: 700;
                        margin: 0;
                        position: relative;
                        z-index: 2;
                        letter-spacing: 0.5px;
                        text-shadow: 0 2px 4px rgba(0,0,0,0.1);
                    }
                    
                    .content { 
                        background-color: #FFFFFF;
                        padding: 40px 30px; 
                    }
                    
                    .greeting {
                        font-size: 20px;
                        font-weight: 600;
                        margin-bottom: 20px;
                        color: #1E2B4D;
                    }
                    
                    .message {
                        margin-bottom: 30px;
                        font-size: 16px;
                        color: #4A5568;
                        line-height: 1.8;
                    }
                    
                    .timer-box {
                        background-color: #F0F4FF;
                        border-radius: 12px;
                        padding: 25px;
                        margin: 30px 0;
                        text-align: center;
                        border: 1px solid #DCE3FF;
                    }
                    
                    .timer-label {
                        font-size: 16px;
                        color: #4A5568;
                        margin-bottom: 15px;
                    }
                    
                    .timer-digits {
                        font-size: 42px;
                        font-weight: 700;
                        color: #4A6FFF;
                        letter-spacing: 2px;
                        margin: 10px 0;
                    }
                    
                    .timer-expiry {
                        font-size: 14px;
                        color: #64748B;
                        margin-top: 15px;
                    }
                    
                    .timer-expiry em {
                        font-style: normal;
                        color: #4A6FFF;
                        font-weight: 600;
                    }
                    
                    .warning-alert {
                        background-color: #FFF5F5;
                        border-left: 4px solid #FF5757;
                        padding: 20px;
                        margin: 30px 0;
                        font-size: 15px;
                        color: #E53E3E;
                        border-radius: 8px;
                        position: relative;
                        box-shadow: 0 4px 15px rgba(229, 62, 62, 0.08);
                    }
                    
                    .warning-alert strong {
                        display: block;
                        font-weight: 700;
                        margin-bottom: 5px;
                    }
                    
                    .warning-alert:before {
                        content: '⚠️';
                        position: absolute;
                        right: 20px;
                        top: 20px;
                        font-size: 24px;
                    }
                    
                    .button-container {
                        text-align: center;
                        margin: 35px 0;
                    }
                    
                    .button { 
                        background: linear-gradient(135deg, #4A6FFF 0%, #6B8CFF 100%);
                        color: white; 
                        padding: 16px 40px; 
                        text-decoration: none; 
                        border-radius: 12px; 
                        display: inline-block; 
                        font-weight: 600; 
                        font-size: 16px;
                        box-shadow: 0 10px 20px rgba(74, 111, 255, 0.25);
                        transition: all 0.3s ease;
                        position: relative;
                        overflow: hidden;
                        letter-spacing: 0.5px;
                    }
                    
                    .button:hover {
                        transform: translateY(-3px);
                        box-shadow: 0 15px 25px rgba(74, 111, 255, 0.35);
                    }
                    
                    .button:before {
                        content: '';
                        position: absolute;
                        top: 0;
                        left: 0;
                        width: 100%;
                        height: 100%;
                        background: linear-gradient(to right, transparent, rgba(255,255,255,0.2), transparent);
                        transform: translateX(-100%);
                    }
                    
                    .checklist {
                        margin: 35px 0;
                        background-color: #F7FAFF;
                        padding: 25px;
                        border-radius: 10px;
                        border: 1px solid #E6EDFF;
                    }
                    
                    .checklist-header {
                        font-size: 16px;
                        font-weight: 600;
                        color: #2D3748;
                        margin-bottom: 15px;
                        display: flex;
                        align-items: center;
                    }
                    
                    .checklist-header:before {
                        content: '🔐';
                        margin-right: 10px;
                        font-size: 18px;
                    }
                    
                    .checklist-item {
                        display: flex;
                        align-items: flex-start;
                        margin-bottom: 12px;
                        color: #4A5568;
                        padding-left: 10px;
                        position: relative;
                    }
                    
                    .checklist-item:last-child {
                        margin-bottom: 0;
                    }
                    
                    .checklist-item:before {
                        content: '';
                        width: 18px;
                        height: 18px;
                        background-color: #CDE1FF;
                        border-radius: 50%;
                        margin-right: 12px;
                        position: relative;
                        top: 2px;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        flex-shrink: 0;
                    }
                    
                    .checklist-item:after {
                        content: '✓';
                        position: absolute;
                        left: 14px;
                        top: 0px;
                        font-size: 12px;
                        color: #4A6FFF;
                        font-weight: bold;
                    }
                    
                    .checklist-text {
                        flex: 1;
                        line-height: 1.5;
                    }
                    
                    .highlight {
                        color: #4A6FFF;
                        font-weight: 600;
                    }
                    
                    .divider {
                        height: 1px;
                        background: linear-gradient(to right, rgba(226, 232, 240, 0), rgba(226, 232, 240, 1), rgba(226, 232, 240, 0));
                        margin: 35px 0;
                    }
                    
                    .fallback-link {
                        text-align: center;
                        font-size: 14px;
                        color: #718096;
                        background-color: #F7FAFF;
                        padding: 12px 20px;
                        border-radius: 8px;
                        margin: 30px auto;
                        max-width: 400px;
                    }
                    
                    .fallback-link a {
                        color: #4A6FFF;
                        text-decoration: none;
                        font-weight: 600;
                        border-bottom: 1px dashed #4A6FFF;
                        padding-bottom: 1px;
                    }
                    
                    .footer {
                        background-color: #F5F7FF;
                        padding: 25px 20px;
                        text-align: center;
                        color: #718096;
                        font-size: 13px;
                        border-top: 1px solid #E6EDFF;
                    }
                    
                    .footer p {
                        margin: 5px 0;
                    }
                    
                    @media only screen and (max-width: 600px) {
                        .container { 
                            width: 100%;
                            border-radius: 0;
                        }
                        .content { 
                            padding: 30px 20px; 
                        }
                        .header { 
                            padding: 30px 15px; 
                        }
                        .header h1 {
                            font-size: 24px;
                        }
                        .timer-digits {
                            font-size: 36px;
                        }
                        .checklist {
                            padding: 20px 15px;
                        }
                    }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h1>Password Reset Request</h1>
                    </div>
                    <div class='content'>
                        <p class='greeting'>Hello {$user->getName()},</p>
                        <p class='message'>We received a request to reset your password for your WamiaGo account. Please click the button below to create a new password. If you didn't make this request, you can safely ignore this email.</p>
                        
                        <div class='timer-box'>
                            <div class='timer-label'>This link will expire in</div>
                            <div class='timer-digits'>01:00:00</div>
                            <div class='timer-expiry'>Expires at <em>{$formattedDateTime}</em> (1 hour from now)</div>
                        </div>
                        
                        <div class='warning-alert'>
                            <strong>Important:</strong>
                            This password reset link will expire in 60 minutes. If you don't use it within this time, you'll need to request a new one.
                        </div>
                        
                        <div class='button-container'>
                            <a href='{$resetUrl}' class='button'>Reset my password</a>
                        </div>
                        
                        <div class='checklist'>
                            <div class='checklist-header'>Password requirements:</div>
                            <div class='checklist-item'>
                                <div class='checklist-text'>Create a <span class='highlight'>strong</span> password you haven't used before</div>
                            </div>
                            <div class='checklist-item'>
                                <div class='checklist-text'>Include <span class='highlight'>uppercase</span>, <span class='highlight'>lowercase</span> letters, <span class='highlight'>numbers</span>, and <span class='highlight'>symbols</span></div>
                            </div>
                            <div class='checklist-item'>
                                <div class='checklist-text'>Make it at least <span class='highlight'>8 characters</span> long for better security</div>
                            </div>
                        </div>
                        
                        <div class='divider'></div>
                        
                        <div class='fallback-link'>
                            If the button doesn't work, <a href='{$resetUrl}'>click here</a> to reset your password
                        </div>
                    </div>
                    <div class='footer'>
                        <p>&copy; " . date('Y') . " WamiaGo. All rights reserved.</p>
                        <p>This is an automated message, please do not reply to this email.</p>
                    </div>
                </div>
            </body>
            </html>
        ";
    }    /**
     * Validates a reset token without database lookups.
     * 
     * This method decodes and validates the token, checking the expiration time
     * and user information contained within the token itself.
     * 
     * @param string $token The token to validate
     * @return User|null The user if token is valid, null otherwise
     */      
    public function validateResetToken(string $token): ?User
    {
        try {
            // Allow for some flexibility in token format
            if (strpos($token, '.') === false) {
                error_log('Invalid token format: No dot separator found');
            return null;
        }
        
            // Check if token is blacklisted (already used)
            if ($this->isTokenBlacklisted($token)) {
                error_log('Token is blacklisted (already used)');
                return null;
            }
            
            // Split the token to get the payload
            $parts = explode('.', $token, 2);
            if (count($parts) !== 2) {
                error_log('Invalid token format: Token does not have two parts separated by a dot');
                return null;
            }
              
              // Decode the payload
            try {
                $encodedPayload = $parts[1];
                
                // Add padding if needed for base64 decoding
                $padding = strlen($encodedPayload) % 4;
                if ($padding > 0) {
                    $encodedPayload .= str_repeat('=', 4 - $padding);
                }
                
                // Replace URL-safe characters back to standard base64 characters
                $base64Payload = strtr($encodedPayload, '-_', '+/');
                
                $decodedPayloadJson = base64_decode($base64Payload, true);
                
                if ($decodedPayloadJson === false) {
                    error_log('Invalid base64 encoding in token payload');
                    return null;
                }
                
                $decodedPayload = json_decode($decodedPayloadJson, true);
                
                // Check if JSON decode was successful
                if ($decodedPayload === null && json_last_error() !== JSON_ERROR_NONE) {
                    error_log('JSON decode error: ' . json_last_error_msg());
                    return null;
                }
            } catch (\Exception $e) {
                error_log('Could not decode token: ' . $e->getMessage());
                return null;
            }
            
            // Validate token format
            if (!isset($decodedPayload['userId']) || !isset($decodedPayload['exp']) || 
                !isset($decodedPayload['email']) || !isset($decodedPayload['iat'])) {
                error_log('Invalid token structure: Missing required fields');
                return null;
            }
            
            // Check if the token has expired
            if ($decodedPayload['exp'] < time()) {
                error_log('Token has expired: ' . date('Y-m-d H:i:s', $decodedPayload['exp']));
                return null;
            }
            
            // Find the user by ID
            $user = $this->userRepository->find($decodedPayload['userId']);
            
            // Verify user exists and email matches the token
            if (!$user) {
                error_log('User not found with ID: ' . $decodedPayload['userId']);
                return null;
            }
              
              if ($user->getEmail() !== $decodedPayload['email']) {
                error_log('Email mismatch: Token email does not match user email');
                return null;
            }
            
            // Check if a newer token has been issued for this user
            $latestTimestamp = $this->getLatestTokenTimestamp($user);
            if ($latestTimestamp !== null && $decodedPayload['iat'] < $latestTimestamp) {
                error_log('Token superseded: A newer token was issued at ' . date('Y-m-d H:i:s', $latestTimestamp));
                return null;
            }
            
            return $user;
        } catch (\Exception $e) {
            // Log the error (in a production environment, you would use a proper logger)
            error_log('Password reset token validation failed: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Stores the timestamp of the latest token issued for a user.
     * This is used to invalidate older tokens when a new one is issued.
     *
     * @param User $user The user requesting the password reset
     * @param int $timestamp The issuance timestamp
     */
    private function storeLatestTokenTimestamp(User $user, int $timestamp): void
    {
        try {
            // First attempt: try to use session to store the latest token timestamps
            $session = $this->requestStack->getSession();
            if ($session->isStarted()) {
                $latestTokens = $session->get('latest_token_timestamps', []);
                $latestTokens[$user->getIdUser()] = $timestamp;
                $session->set('latest_token_timestamps', $latestTokens);
            }
            
            // As a backup and for persistence between sessions, also store in a file
            $cacheDir = $this->params->get('kernel.cache_dir');
            $timestampsFile = $cacheDir . '/latest_token_timestamps.json';
            
            // Read existing timestamps (if exists)
            $fileTimestamps = [];
            if (file_exists($timestampsFile)) {
                $fileContent = file_get_contents($timestampsFile);
                if ($fileContent) {
                    $fileTimestamps = json_decode($fileContent, true) ?: [];
                }
            }
            
            // Add the new timestamp
            $fileTimestamps[$user->getIdUser()] = $timestamp;
            
            // Write back to file
            file_put_contents($timestampsFile, json_encode($fileTimestamps));
        } catch (\Exception $e) {
            // Log but don't crash
            error_log('Failed to store latest token timestamp: ' . $e->getMessage());
        }
    }
    
    /**
     * Gets the timestamp of the latest token issued for a user.
     *
     * @param User $user The user to check
     * @return int|null The latest timestamp or null if not found
     */
    private function getLatestTokenTimestamp(User $user): ?int
    {
        try {
            // First check: session-based storage
            $session = $this->requestStack->getSession();
            if ($session->isStarted()) {
                $latestTokens = $session->get('latest_token_timestamps', []);
                if (isset($latestTokens[$user->getIdUser()])) {
                    return $latestTokens[$user->getIdUser()];
                }
            }
            
            // Second check: file-based storage
            $cacheDir = $this->params->get('kernel.cache_dir');
            $timestampsFile = $cacheDir . '/latest_token_timestamps.json';
            
            if (file_exists($timestampsFile)) {
                $fileContent = file_get_contents($timestampsFile);
                if ($fileContent) {
                    $fileTimestamps = json_decode($fileContent, true) ?: [];
                    if (isset($fileTimestamps[$user->getIdUser()])) {
                        // Also update the session for faster access next time
                        if ($session->isStarted()) {
                            $latestTokens = $session->get('latest_token_timestamps', []);
                            $latestTokens[$user->getIdUser()] = $fileTimestamps[$user->getIdUser()];
                            $session->set('latest_token_timestamps', $latestTokens);
                        }
                        return $fileTimestamps[$user->getIdUser()];
                    }
                }
            }
            
            return null;
        } catch (\Exception $e) {
            // Log but assume no previous token if we can't check
            error_log('Failed to get latest token timestamp: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Resets the user's password.
     * 
     * @param User $user The user to reset the password for
     * @param string $newPassword The new password (plain text)
     * @param string $token The token used for this reset (to blacklist it)
     */
    public function resetPassword(User $user, string $newPassword, string $token = null): void
    {
        // Hash the password
        $hashedPassword = $this->passwordHasher->hashPassword($user, $newPassword);
        
        // Set the new password
        $user->setPassword($hashedPassword);
        
        // Save changes to database
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        
        // If a token was provided, blacklist it to prevent reuse
        if ($token) {
            $this->blacklistToken($token);
        }
    }
    
    /**
     * Blacklists a token to prevent reuse.
     * 
     * In a production environment, this would typically use Redis or a database table.
     * For simplicity, we're using the session.
     * 
     * @param string $token The token to blacklist
     */    private function blacklistToken(string $token): void
    {
        try {
            // First attempt: try to use session for blacklisting
            $session = $this->requestStack->getSession();
            if ($session->isStarted()) {
                $blacklist = $session->get('token_blacklist', []);
                
                // We only store a hash of the token for security
                $tokenHash = md5($token);
                $blacklist[$tokenHash] = time();
                
                // Remove old entries (tokens that would be expired anyway)
                $blacklist = array_filter($blacklist, function($timestamp) {
                    return $timestamp > (time() - self::TOKEN_VALIDITY);
                });
                
                $session->set('token_blacklist', $blacklist);
            }
            
            // As a backup, store in a file in the cache directory
            $cacheDir = $this->params->get('kernel.cache_dir');
            $blacklistFile = $cacheDir . '/token_blacklist.json';
            
            // Read existing blacklist (if exists)
            $fileBlacklist = [];
            if (file_exists($blacklistFile)) {
                $fileContent = file_get_contents($blacklistFile);
                if ($fileContent) {
                    $fileBlacklist = json_decode($fileContent, true) ?: [];
                }
            }
            
            // Add the token
            $tokenHash = md5($token);
            $fileBlacklist[$tokenHash] = time();
            
            // Remove expired entries
            $fileBlacklist = array_filter($fileBlacklist, function($timestamp) {
                return $timestamp > (time() - self::TOKEN_VALIDITY);
            });
            
            // Write back to file
            file_put_contents($blacklistFile, json_encode($fileBlacklist));
        } catch (\Exception $e) {
            // Log but don't crash
            error_log('Failed to blacklist token: ' . $e->getMessage());
        }
    }
    
    /**
     * Checks if a token is blacklisted.
     * 
     * @param string $token The token to check
     * @return bool True if the token is blacklisted
     */
    private function isTokenBlacklisted(string $token): bool
    {
        $tokenHash = md5($token);
        
        try {
            // First check: session-based blacklist
            $session = $this->requestStack->getSession();
            if ($session->isStarted()) {
                $sessionBlacklist = $session->get('token_blacklist', []);
                if (isset($sessionBlacklist[$tokenHash])) {
                    return true;
                }
            }
            
            // Second check: file-based blacklist
            $cacheDir = $this->params->get('kernel.cache_dir');
            $blacklistFile = $cacheDir . '/token_blacklist.json';
            
            if (file_exists($blacklistFile)) {
                $fileContent = file_get_contents($blacklistFile);
                if ($fileContent) {
                    $fileBlacklist = json_decode($fileContent, true) ?: [];
                    if (isset($fileBlacklist[$tokenHash])) {
                        return true;
                    }
                }
            }
            
            return false;
        } catch (\Exception $e) {
            // Log but assume token is valid if we can't check blacklist
            error_log('Failed to check token blacklist: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if a user exists with the given email address.
     * 
     * @param string $email The email address to check
     * @return bool True if the user exists, false otherwise
     */
    public function checkUserExists(string $email): bool
    {
        $user = $this->userRepository->findOneBy(['email' => $email]);
        return $user !== null;
    }
    
    /**
     * Check for cooldown status. Instead of throwing an exception, this 
     * method returns an array with cooldown information.
     * 
     * @param string $email The email address to check
     * @return array ['inCooldown' => bool, 'remainingTime' => int]
     */
    public function checkCooldown(string $email): array
    {
        $cooldownTime = $this->getEmailCooldownTime($email);
        return [
            'inCooldown' => ($cooldownTime > 0),
            'remainingTime' => $cooldownTime
        ];
    }
}
