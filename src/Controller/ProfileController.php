<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\UserRepository;
use App\Service\TwoFactorSessionHandler;
use Symfony\Component\HttpFoundation\JsonResponse;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Totp\TotpAuthenticatorInterface;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelHigh;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\RoundBlockSizeMode\RoundBlockSizeModeMargin;
use Endroid\QrCode\Writer\PngWriter;

class ProfileController extends AbstractController
{
    private UserRepository $userRepository;
    private TwoFactorSessionHandler $twoFactorSessionHandler;
    
    public function __construct(
        UserRepository $userRepository,
        TwoFactorSessionHandler $twoFactorSessionHandler
    ) 
    {
        $this->userRepository = $userRepository;
        $this->twoFactorSessionHandler = $twoFactorSessionHandler;
    }
    
    #[Route('/profile', name: 'app_profile')]
    public function index(): Response
    {
        $user = $this->getUser();
        
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // Redirect to the new profile route to avoid conflicts
        return $this->redirectToRoute('app_profile');
    }
    
    #[Route('/profile/edit', name: 'app_profile_edit')]
    public function edit(): Response
    {
        // You'll implement this method for profile editing
        return $this->render('front/profile_edit.html.twig', [
            'user' => $this->getUser()
        ]);
    }
    
    #[Route('/profile/two-factor-setup', name: 'app_profile_2fa_setup')]
    public function twoFactorSetup(TotpAuthenticatorInterface $totpAuthenticator, Request $request): Response
    {
        $user = $this->getUser();
        
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }
        
        // Check if 2FA is already enabled
        $twoFactorEnabled = $this->twoFactorSessionHandler->is2faEnabled();
        if ($twoFactorEnabled) {
            return $this->redirectToRoute('app_profile');
        }
        
        // Generate a new TOTP secret
        $secret = $this->twoFactorSessionHandler->setTemporaryTotpSecret();
        
        // Create the QR code content
        $qrContent = $totpAuthenticator->getQRContent($user);
          // If this is an AJAX request, return JSON data
        if ($request->isXmlHttpRequest() || $request->query->has('ajax')) {
            try {
                // Generate QR code image
                $writer = new PngWriter();
                $qrCode = new QrCode($qrContent);
                $qrCode->setEncoding(new Encoding('UTF-8'));
                $qrCode->setErrorCorrectionLevel(new ErrorCorrectionLevelHigh());
                $qrCode->setSize(300);
                $qrCode->setMargin(10);
                $qrCode->setRoundBlockSizeMode(new RoundBlockSizeModeMargin());
                $qrCode->setForegroundColor(new Color(0, 0, 0));
                
                $result = $writer->write($qrCode);
                
                // Return QR code as data URL
                $response = $this->json([
                    'qrCodeUrl' => $result->getDataUri(),
                    'secret' => $secret,
                ]);
                
                // Set CORS headers to allow the request from the same origin
                $response->headers->set('Access-Control-Allow-Origin', '*');
                $response->headers->set('Access-Control-Allow-Methods', 'GET, POST');
                
                return $response;
            } catch (\Exception $e) {
                return $this->json([
                    'error' => true,
                    'message' => $e->getMessage()
                ], 500);
            }
        }

        return $this->render('profile/2fa_setup.html.twig', [
            'user' => $user,
            'secret' => $secret,
            'qrContent' => $qrContent,
        ]);
    }
    
    private function calculateProfileCompletionPercentage($user): int
    {
        $completion = 0;
        
      
        if ($user->getProfilePicture()) {
            $completion += 30;
        }
        
    
        if ($user->isVerified()) {
            $completion += 20;
        }
        
        $completion += $user->getName() ? 4 : 0;
        $completion += $user->getEmail() ? 4 : 0;
        $completion += $user->getPhoneNumber() ? 4 : 0;
        $completion += $user->getDateOfBirth() ? 4 : 0;
        $completion += ($user->getLocation() !== null) ? 4 : 0;
        
    
          return min($completion, 100);
    }
    
    #[Route('/profile/two-factor-qr-code', name: 'app_2fa_qr_code')]
    public function generateQrCode(TotpAuthenticatorInterface $totpAuthenticator): Response
    {
        $user = $this->getUser();
        
        if (!$user) {
            throw $this->createAccessDeniedException('Access denied');
        }
        
        // Generate a new TOTP secret or get the existing one
        $secret = $this->twoFactorSessionHandler->getTemporaryTotpSecret();
        if (!$secret) {
            $secret = $this->twoFactorSessionHandler->setTemporaryTotpSecret();
        }
        
        // Create the QR code content
        $qrContent = $totpAuthenticator->getQRContent($user);
        
        // Generate QR code image
        $writer = new PngWriter();
        $qrCode = new QrCode($qrContent);
        $qrCode->setEncoding(new Encoding('UTF-8'));
        $qrCode->setErrorCorrectionLevel(new ErrorCorrectionLevelHigh());
        $qrCode->setSize(300);
        $qrCode->setMargin(10);
        $qrCode->setRoundBlockSizeMode(new RoundBlockSizeModeMargin());
        $qrCode->setForegroundColor(new Color(0, 0, 0));
        
        $result = $writer->write($qrCode);
        
        // Return image response
        $response = new Response($result->getString());
        $response->headers->set('Content-Type', 'image/png');
        return $response;
    }
}