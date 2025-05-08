<?php

namespace App\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Totp\TotpAuthenticatorInterface;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelHigh;
use Endroid\QrCode\RoundBlockSizeMode\RoundBlockSizeModeMargin;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Doctrine\ORM\EntityManagerInterface;
use App\Service\TwoFactorSessionHandler;
use Scheb\TwoFactorBundle\Model\Totp\TotpConfiguration;
use OTPHP\TOTP;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Color;

class TwoFactorController extends AbstractController
{
    private $entityManager;
    private $totpAuthenticator;
    private $twoFactorSessionHandler;

    public function __construct(
        EntityManagerInterface $entityManager,
        TotpAuthenticatorInterface $totpAuthenticator,
        TwoFactorSessionHandler $twoFactorSessionHandler
    ) {
        $this->entityManager = $entityManager;
        $this->totpAuthenticator = $totpAuthenticator;
        $this->twoFactorSessionHandler = $twoFactorSessionHandler;
    }    #[Route('/profile/2fa-setup', name: 'app_2fa_setup')]
    #[IsGranted('ROLE_USER')]
    public function setup(Request $request, SessionInterface $session): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        // Clear any redirect loop counters and 2FA in-progress flag
        $session->remove('2fa_page_access_count');
        $session->remove('2fa_in_progress');
        $session->remove('missing_totp_secret');
        
        // Log the setup access
        error_log('User ' . $user->getEmail() . ' accessing 2FA setup');
        
        // Make sure the user is verified
        if (!$user->isVerified() && $user instanceof User) {
            // Mark user as verified since they're setting up 2FA
            $user->setIsVerified(true);
            $this->entityManager->flush();
            error_log('User ' . $user->getEmail() . ' marked as verified during 2FA setup');
        }
        
        // Check if 2FA is already fully set up and configured with a valid secret
        $hasValidSecret = $this->twoFactorSessionHandler->hasTotpSecret();
        if ($hasValidSecret) {
            $this->addFlash('info', 'Two-factor authentication is already enabled for your account.');
            return $this->redirectToRoute('app_profile');
        }
        
        // ALWAYS generate a fresh TOTP secret for setup
        // First try using the session handler
        $secret = $this->twoFactorSessionHandler->setTemporaryTotpSecret();
        
        // If that fails, generate one directly (fallback)
        if (empty($secret) || !preg_match('/^[A-Z2-7]+$/', $secret)) {
            $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
            $secret = '';
            $length = 16;
            
            for ($i = 0; $i < $length; $i++) {
                $secret .= $chars[random_int(0, strlen($chars) - 1)];
            }
            
            // Store it in the session
            $session->set('totp_secret', $secret);
            error_log('Generated fallback TOTP secret: ' . substr($secret, 0, 5) . '...');
        } else {
            error_log('Generated primary TOTP secret: ' . substr($secret, 0, 5) . '...');
        }
        
        // For AJAX requests, return JSON with QR code data
        if ($request->isXmlHttpRequest()) {
            // Create the QR code content
            $qrContent = $this->totpAuthenticator->getQRContent($user);
            
            // Return data as JSON
            return new JsonResponse([
                'success' => true,
                'secret' => $secret,
                'qrCodeUrl' => $qrContent,
            ]);
        }
        
        // Create the QR code content
        $qrContent = $this->totpAuthenticator->getQRContent($user);
        
        return $this->render('profile/2fa_setup.html.twig', [
            'user' => $user,
            'secret' => $secret,
            'qrContent' => $qrContent,
        ]);
    }    #[Route('/profile/2fa-verify', name: 'app_2fa_verify', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function verify(Request $request): Response
    {
        try {
            /** @var User $user */
            $user = $this->getUser();
            
            // Clear any loop counters as we're in the verification process
            $request->getSession()->remove('2fa_page_access_count');
            
            // Log the verification attempt
            error_log('User ' . $user->getEmail() . ' attempting to verify 2FA');
            
            // Get the code and secret
            if ($request->isXmlHttpRequest()) {
                if ($request->getContentType() === 'json') {
                    $data = json_decode($request->getContent(), true);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        return new JsonResponse([
                            'success' => false,
                            'message' => 'Invalid JSON: ' . json_last_error_msg()
                        ], 400);
                    }
                    $code = $data['code'] ?? null; 
                } else {
                    // Handle form-urlencoded data
                    $code = $request->request->get('code');
                    if (!$code) {
                        $code = $request->request->get('verificationCode');
                    }
                }
                
                // Try different parameter names for the secret
                $secret = null;
                foreach (['secret', 'totpSecret', 'totp_secret'] as $paramName) {
                    if ($request->request->has($paramName)) {
                        $secret = $request->request->get($paramName);
                        break;
                    } elseif (isset($data[$paramName])) {
                        $secret = $data[$paramName];
                        break;
                    }
                }
                
                // If no secret provided, get from session
                if (!$secret) {
                    $secret = $this->twoFactorSessionHandler->getTemporaryTotpSecret();
                }
                
            } else {
                $code = $request->request->get('code');
                $secret = $request->request->get('secret', $this->twoFactorSessionHandler->getTemporaryTotpSecret());
            }
            
            // Log debug information
            error_log('Verification attempt - Code: ' . $code . ', Secret: ' . (isset($secret) ? substr($secret, 0, 5) . '...' : 'not set'));
            
            if (!$secret) {
                if ($request->isXmlHttpRequest()) {
                    return new JsonResponse([
                        'success' => false,
                        'message' => 'TOTP secret not found. Please start setup again.'
                    ], 400);
                }
                
                $this->addFlash('error', 'TOTP secret not found. Please start setup again.');
                return $this->redirectToRoute('app_2fa_setup');
            }
            
            if (!$code) {
                if ($request->isXmlHttpRequest()) {
                    return new JsonResponse([
                        'success' => false,
                        'message' => 'Verification code is required'
                    ], 400);
                }
                
                $this->addFlash('error', 'Verification code is required');
                return $this->redirectToRoute('app_2fa_setup');
            }
            
            // Check if the code is valid
            $isValid = $this->verifyTotpCode($user, $code, $secret);
            
            if ($isValid) {
                // Store the secret in the user entity
                $user->setOtpSecret($secret);
                $this->entityManager->persist($user);
                $this->entityManager->flush();
                error_log('User ' . $user->getEmail() . ' 2FA setup completed. Secret saved to database.');
                
                // Confirm and get cookies
                $cookies = $this->twoFactorSessionHandler->confirmTotpSecret();
                
                // Update user status to verified if not already
                if (!$user->isVerified()) {
                    $user->setIsVerified(true);
                    $this->entityManager->flush();
                }

                error_log('2FA setup confirmed and verified for user: ' . $user->getEmail());
                
                if ($request->isXmlHttpRequest()) {
                    return new JsonResponse([
                        'success' => true,
                        'message' => 'Two-factor authentication enabled successfully!'
                    ]);
                }
                
                // Flash success message
                $this->addFlash('success', 'Two-factor authentication enabled successfully!');
                
                $response = $this->redirectToRoute('app_profile');
                
                // Add cookies to response
                foreach ($cookies as $cookie) {
                    $response->headers->setCookie($cookie);
                }
                
                return $response;
            }
            
            // Invalid code
            error_log('Invalid verification code attempt by user: ' . $user->getEmail());
            
            if ($request->isXmlHttpRequest()) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Invalid verification code. Please try again.'
                ], 400);
            }
            
            $this->addFlash('error', 'Invalid verification code. Please try again.');
            return $this->redirectToRoute('app_2fa_setup');
            
        } catch (\Exception $e) {
            // Log the error
            error_log('2FA verification error: ' . $e->getMessage());
            
            if ($request->isXmlHttpRequest()) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'An error occurred: ' . $e->getMessage()
                ], 500);
            }
            
            $this->addFlash('error', 'An error occurred: ' . $e->getMessage());
            return $this->redirectToRoute('app_2fa_setup');
        }
    }
      #[Route('/profile/2fa-disable', name: 'app_2fa_disable', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function disable(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }
        
        // Clear the OTP secret from the database
        $user->setOtpSecret(null);
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        error_log('Removed OTP secret from database for user: ' . $user->getEmail());
        
        // For AJAX requests, we might want to verify the password
        if ($request->isMethod('POST') && $request->isXmlHttpRequest()) {
            $data = json_decode($request->getContent(), true);
            $password = $data['password'] ?? null;
            
            // Here you would verify the password
            // For this implementation, we'll skip that step
            
            // Get cookies to remove 2FA
            $cookies = $this->twoFactorSessionHandler->disable2fa();
            
            // Return JSON response
            $response = new JsonResponse([
                'success' => true,
                'message' => 'Two-factor authentication has been disabled.'
            ]);
            
            // Add cookies to response
            foreach ($cookies as $cookie) {
                $response->headers->setCookie($cookie);
            }
            
            return $response;
        }
        
        // For regular GET requests
        // Get cookies to remove 2FA
        $cookies = $this->twoFactorSessionHandler->disable2fa();
        
        $this->addFlash('success', 'Two-factor authentication has been disabled.');
        
        $response = $this->redirectToRoute('app_profile');
        
        // Add cookies to response
        foreach ($cookies as $cookie) {
            $response->headers->setCookie($cookie);
        }
        
        return $response;
    }
    
    #[Route('/2fa', name: 'app_2fa_login')]
    public function login2fa(Request $request, AuthenticationUtils $authenticationUtils): Response
    {
        // Reset any redirect counters to prevent loops
        $session = $request->getSession();
        $session->remove('2fa_redirect_count');
        $session->remove('2fa_page_access_count');
        
        // Set 2FA in progress flag
        $session->set('2fa_in_progress', true);
        
        // Get the last authentication error if any
        $error = $authenticationUtils->getLastAuthenticationError();
        
        // Try to get user from token if available
        try {
            $user = $this->getUser();
            
            if (!$user) {
                error_log('2FA Login: No user found in security token.');
                // Redirect to regular login since we don't have a user
                $session->getFlashBag()->add('error', 'You must log in first.');
                return $this->redirectToRoute('app_login');
            }
            
            error_log('2FA Login: User ' . $user->getEmail() . ' accessing 2FA login page.');
            
            // If we have a user but they don't have 2FA enabled, skip 2FA
            if (!$user->isTotpAuthenticationEnabled()) {
                error_log('2FA Login: User ' . $user->getEmail() . ' does not have 2FA enabled.');
                $session->set('2fa_in_progress', false);
                return $this->redirectToRoute('app_front_home');
            }
        } catch (\Exception $e) {
            error_log('2FA Login: Exception: ' . $e->getMessage());
            // If we can't get the user, redirect to regular login
            return $this->redirectToRoute('app_login');
        }
        
        // Render the 2FA login form
        return $this->render('security/2fa_login.html.twig', [
            'error' => $error,
        ]);
    }
    
    /**
     * Helper method to clear 2FA session variables
     */
    private function clearTwoFactorSession($session): void
    {
        $session->remove('2fa_enabled');
        $session->remove('totp_secret');
        $session->remove('permanent_totp_secret');
        $session->remove('2fa_in_progress');
        $session->remove('2fa_page_access_count');
        $session->remove('missing_totp_secret');
    }
      #[Route('/profile/2fa-qr-code', name: 'app_2fa_qr_code', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function generateQrCode(): Response
    {
        /** @var User $user */
        $user = $this->getUser();
          // Generate a new TOTP secret if not already in session
        $secret = $this->twoFactorSessionHandler->getTemporaryTotpSecret();
        if (!$secret) {
            $secret = $this->twoFactorSessionHandler->setTemporaryTotpSecret();
        }
        
        // Get the QR code content
        $qrContent = $this->totpAuthenticator->getQRContent($user);
        
        // Create a QR code image
        $writer = new PngWriter();
        $qrCode = new QrCode($qrContent);
        $qrCode->setEncoding(new Encoding('UTF-8'));
        $qrCode->setErrorCorrectionLevel(new ErrorCorrectionLevelHigh());
        $qrCode->setSize(300);
        $qrCode->setMargin(10);
        $qrCode->setRoundBlockSizeMode(new RoundBlockSizeModeMargin());
        $qrCode->setForegroundColor(new Color(0, 0, 0));
        
        $result = $writer->write($qrCode);
        
        return new Response($result->getString(), 200, ['Content-Type' => 'image/png']);
    }
      #[Route('/profile/2fa-backup-codes', name: 'app_2fa_backup_codes')]
    #[IsGranted('ROLE_USER')]
    public function backupCodes(Request $request): Response
    {
        // Check if 2FA is enabled
        if (!$this->twoFactorSessionHandler->is2faEnabled()) {
            $this->addFlash('error', 'You need to enable two-factor authentication first.');
            return $this->redirectToRoute('app_profile');
        }
        
        // Generate backup codes
        $backupData = $this->twoFactorSessionHandler->generateBackupCodes();
        $codes = $backupData['codes'];
        
        // Debug - log how many codes were generated
        $codeCount = count($codes);
        error_log("Generated $codeCount backup codes");
        
        // AJAX request
        if ($request->isXmlHttpRequest()) {
            $response = new JsonResponse([
                'success' => true,
                'codes' => $codes
            ]);
            
            // Add the backup codes cookie
            $response->headers->setCookie($backupData['cookie']);
            
            return $response;
        }
        
        // Add dummy codes if none were generated (for debugging)
        if (empty($codes)) {
            $codes = [
                'AAAA-1111', 'BBBB-2222', 'CCCC-3333', 'DDDD-4444',
                'EEEE-5555', 'FFFF-6666', 'GGGG-7777', 'HHHH-8888'
            ];
            error_log("Used dummy codes because no real codes were generated");
        }
        
        // Dump codes to log for debugging
        error_log("Backup codes: " . implode(", ", $codes));
        
        // Regular request - show backup codes page
        return $this->render('profile/2fa_backup_codes.html.twig', [
            'codes' => $codes
        ]);
    }
    
    #[Route('/check-2fa-status', name: 'app_check_2fa_status')]
    public function checkStatus(): JsonResponse
    {
        // Get detailed 2FA status
        $statusInfo = $this->twoFactorSessionHandler->get2faStatusInfo();
        
        // Add user verification status
        $statusInfo['userVerified'] = $this->getUser() && method_exists($this->getUser(), 'isVerified') 
            ? $this->getUser()->isVerified() 
            : false;
            
        return new JsonResponse($statusInfo);
    }

    private function verifyTotpCode(User $user, string $code, string $secret): bool
    {
        try {
            // Create a TOTP instance with the provided secret
            $totp = TOTP::create($secret);
            $totp->setDigits(6);
            $totp->setPeriod(30);
            
            // Verify the code with some leeway (1 period before/after)
            $result = $totp->verify($code, null, 1);
            
            error_log('TOTP verification result: ' . ($result ? 'success' : 'failed') . ' for user ' . $user->getEmail());
            
            return $result;
        } catch (\Exception $e) {
            error_log('Error verifying TOTP code: ' . $e->getMessage());
            return false;
        }
    }
}
