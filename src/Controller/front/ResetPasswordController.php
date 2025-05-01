<?php

namespace App\Controller\front;

use App\Entity\User;
use App\Form\ResetPasswordRequestFormType;
use App\Form\ResetPasswordFormType;
use App\Service\ResetPasswordService;
use App\Service\SecurityNotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[Route('/reset-password')]
class ResetPasswordController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ResetPasswordService $resetPasswordService,
        private UserPasswordHasherInterface $passwordHasher,
        private SecurityNotificationService $notificationService
    ) {
    }
    
    #[Route('', name: 'app_forgot_password_request')]
    public function request(Request $request): Response
    {
        $form = $this->createForm(ResetPasswordRequestFormType::class);
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
                    return $this->render('front/reset_password/email_sent.html.twig', [
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

        return $this->render('front/reset_password/request.html.twig', [
            'requestForm' => $form->createView(),
            'cooldown' => $cooldown,
            'emailWasSubmitted' => $emailWasSubmitted,            'emailAddress' => $emailAddress
        ]);
    }
      #[Route('/reset/{token}', name: 'app_reset_password')]
    public function reset(Request $request, string $token): Response
    {
        error_log('============= PASSWORD RESET PROCESS STARTED ============');
        
        // URL decode the token to handle URL encoding issues
        $token = urldecode($token);
        
        // Validate the token format before even trying to process it
        if (strpos($token, '.') === false) {
            $this->addFlash('danger', 'The password reset link is invalid. Please request a new one.');
            return $this->redirectToRoute('app_forgot_password_request');
        }
        
        $user = $this->resetPasswordService->validateResetToken($token);
        
        if (!$user) {
            // Token could be invalid for multiple reasons, give a specific message
            $this->addFlash('danger', 'Your password reset link is invalid or has expired. Please request a new one.');
            return $this->redirectToRoute('app_forgot_password_request');
        }

        $form = $this->createForm(ResetPasswordFormType::class);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {            
            try {
                error_log('Form submitted, about to reset password');
                // Use the service to reset the password
                $this->resetPasswordService->resetPassword(
                    $user,
                    $form->get('plainPassword')->getData(),
                    $token // Pass the token so it can be blacklisted
                );
                error_log('Password successfully reset for user: ' . $user->getEmail());

                // Add success message
                $this->addFlash('success', 'Your password has been reset successfully. You can now log in with your new password.');
                error_log('============= PASSWORD RESET PROCESS COMPLETED ============');

                return $this->redirectToRoute('app_login');
            } catch (\Exception $e) {
                error_log('Error occurred: ' . $e->getMessage());
                $this->addFlash('danger', 'An error occurred while resetting your password. Please try again.');
            }
        }        
        return $this->render('front/reset_password/reset.html.twig', [
            'resetForm' => $form->createView(),
        ]);
    }
    
    #[Route('/resend/{email}', name: 'app_resend_password_reset_email', methods: ['POST'])]
    public function resendEmail(Request $request, string $email): Response
    {
        // URL-decode the email to handle non-ASCII characters
        $email = urldecode($email);
        
        // Check CSRF token to prevent abuse
        $submittedToken = $request->request->get('_csrf_token');
        if (!$this->isCsrfTokenValid('resend-email' . $email, $submittedToken)) {
            return $this->json([
                'success' => false,
                'message' => 'Invalid CSRF token',
            ], Response::HTTP_BAD_REQUEST);
        }
        
        try {
            $result = $this->resetPasswordService->processForgotPasswordRequest($email);
            
            if ($result['success']) {
                return $this->json([
                    'success' => true,
                    'message' => 'Reset email has been sent again.',
                    'cooldown' => $result['cooldown'] ?? 60
                ]);            } else {
                return $this->json([
                    'success' => false,
                    'message' => $result['error'] ?? 'Unable to send reset email.',
                    'cooldown' => $result['cooldown'] ?? 0
                ]);
            }
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}