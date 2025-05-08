<?php

namespace App\Security;

use App\Service\TwoFactorSessionHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;

/**
 * Special controller to handle access to 2FA pages
 */
class TwoFactorAccessController extends AbstractController
{
    private TwoFactorSessionHandler $twoFactorSessionHandler;

    public function __construct(TwoFactorSessionHandler $twoFactorSessionHandler)
    {
        $this->twoFactorSessionHandler = $twoFactorSessionHandler;
    }

    /**
     * This route acts as a wrapper for the 2FA setup process to avoid the 2FA check
     */
    #[Route('/profile/two-factor-access', name: 'app_profile_two_factor_access')]
    public function accessTwoFactorSetup(Request $request): Response
    {
        // Ensure user is logged in
        if (!$this->isGranted('IS_AUTHENTICATED_FULLY')) {
            return $this->redirectToRoute('app_login');
        }

        // Mark that we're in 2FA setup mode to bypass restrictions
        $request->getSession()->set('two_factor_setup_in_progress', true);
        
        // Redirect to the actual 2FA setup page
        return $this->redirectToRoute('app_2fa_setup');
    }
} 