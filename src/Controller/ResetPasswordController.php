<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\ResetPasswordRequestType;
use App\Form\ResetPasswordType;
use App\Repository\UserRepository;
use App\Service\ResetPasswordService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;

class ResetPasswordController extends AbstractController
{
    private $resetPasswordService;
    private $userRepository;
    private $entityManager;
    private $passwordHasher;

    public function __construct(
        ResetPasswordService $resetPasswordService,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ) {
        $this->resetPasswordService = $resetPasswordService;
        $this->userRepository = $userRepository;
        $this->entityManager = $entityManager;
        $this->passwordHasher = $passwordHasher;
    }
    
    /**
     * Handles resending reset password email with CSRF protection
     */
    #[Route('/reset-password/resend-email/{email}', name: 'app_resend_password_reset_email_admin')]
    public function resendResetEmail(string $email, Request $request): Response
    {
        // CSRF protection
        if (!$this->isCsrfTokenValid('resend-email' . $email, $request->request->get('_csrf_token'))) {
            return $this->json([
                'success' => false,
                'message' => 'Invalid CSRF token.'
            ], 400);
        }

        try {
            $result = $this->resetPasswordService->processForgotPasswordRequest($email);
            
            if ($result['success']) {
                return $this->json([
                    'success' => true,
                    'message' => 'Reset email has been sent again.',
                    'cooldown' => $result['cooldown'] ?? 60
                ]);
            } else {
                return $this->json([
                    'success' => false,
                    'message' => $result['error'] ?? 'Unable to send reset email. Please try again later.',
                    'cooldown' => $result['cooldown'] ?? 0
                ]);
            }        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'An error occurred while processing your request.'
            ], 500);
        }
    }
    
    #[Route('/forgot-password', name: 'app_forgot_password')]
    public function forgotPassword(Request $request): Response
    {
        $form = $this->createForm(ResetPasswordRequestType::class);
        $form->handleRequest($request);

        // Store for template variables
        $cooldown = 0;
        $emailWasSubmitted = false;
        $emailAddress = '';

        if ($form->isSubmitted() && $form->isValid()) {
            $email = $form->get('email')->getData();            $emailAddress = $email; // Save for possible resend action
            $emailWasSubmitted = true;
            try {
                $result = $this->resetPasswordService->processForgotPasswordRequest($email);
                if ($result['success']) {
                    // Instead of flash message, redirect to a dedicated page with better UX
                    return $this->render('reset_password/email_sent.html.twig', [
                        'email' => $email,
                        'cooldown' => $result['cooldown'] ?? 60
                    ]);
                } else {
                    if (isset($result['error'])) {
                        $this->addFlash('danger', $result['error']);
                    } else if (isset($result['cooldown']) && $result['cooldown'] > 0) {
                        $this->addFlash('warning', 'Please wait before requesting another reset email. You can try again in ' . $result['cooldown'] . ' seconds.');
                        $cooldown = $result['cooldown'];
                    } else if (isset($result['userExists']) && $result['userExists'] === false) {
                        // User doesn't exist - be direct but don't provide too much information
                        $this->addFlash('danger', 'No account was found with this email address.');
                    } else {
                        // Fallback message for other error cases
                        $this->addFlash('danger', 'Unable to process your request. Please try again later.');
                    }
                }
            } catch (\Exception $e) {
                $this->addFlash('danger', 'An error occurred while processing your request: ' . $e->getMessage());
            }
        }

        return $this->render('reset_password/request.html.twig', [
            'form' => $form->createView(),
            'cooldown' => $cooldown,
            'emailWasSubmitted' => $emailWasSubmitted,
            'emailAddress' => $emailAddress
        ]);
    }

    #[Route('/reset-password/{token}', name: 'app_reset_password')]
    public function resetPassword(Request $request, string $token): Response
    {
        // URL decode the token to handle URL encoding issues
        $token = urldecode($token);
        
        // Validate the token format before even trying to process it
        if (strpos($token, '.') === false) {
            $this->addFlash('danger', 'The password reset link is invalid. Please request a new one.');
            return $this->redirectToRoute('app_forgot_password');
        }
        
        $user = $this->resetPasswordService->validateResetToken($token);
        
        if (!$user) {
            $this->addFlash('danger', 'Your password reset link is invalid or has expired. Please request a new one.');
            return $this->redirectToRoute('app_forgot_password');
        }

        $form = $this->createForm(ResetPasswordType::class);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                // Use the service to reset the password and blacklist the token
                $this->resetPasswordService->resetPassword(
                    $user,
                    $form->get('plainPassword')->getData(),
                    $token
                );
                
                $this->addFlash('success', 'Your password has been successfully reset. You can now log in with your new password.');
                return $this->redirectToRoute('app_login');
            } catch (\Exception $e) {
                $this->addFlash('danger', 'An error occurred while resetting your password. Please try again.');
            }
        }

        return $this->render('reset_password/reset.html.twig', [
            'resetForm' => $form->createView(),
        ]);
    }
}
