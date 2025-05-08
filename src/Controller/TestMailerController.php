<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\SecurityNotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class TestMailerController extends AbstractController
{
    #[Route('/test-mailer', name: 'app_test_mailer')]
    public function index(MailerInterface $mailer, ParameterBagInterface $params): Response
    {
        try {
            // Get the mailer DSN from environment variable
            $mailerDsn = $_ENV['MAILER_DSN'] ?? 'Not found';
            
            // Test email
            $email = (new Email())
                ->from('noreply@wamiago.com')
                ->to('test@example.com')
                ->subject('Test Email')
                ->text('This is a test email.')
                ->html('<p>This is a test email.</p>');
            
            // Try to send the email
            $mailer->send($email);
            
            // Return configurations and status
            return $this->json([
                'success' => true,
                'message' => 'Email sent successfully',
                'mailer_dsn' => preg_replace('/:[^:]*@/', ':***@', $mailerDsn), // Hide password
                'environment' => $_ENV['APP_ENV'] ?? 'unknown',
                'messenger_transport' => $_ENV['MESSENGER_TRANSPORT_DSN'] ?? 'Not found'
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Error sending email: ' . $e->getMessage(),
                'mailer_dsn' => preg_replace('/:[^:]*@/', ':***@', $mailerDsn), // Hide password
                'environment' => $_ENV['APP_ENV'] ?? 'unknown'
            ], 500);
        }
    }
    
    #[Route('/test-notification', name: 'app_test_notification')]
    public function testNotification(
        EntityManagerInterface $entityManager, 
        SecurityNotificationService $notificationService
    ): Response
    {
        // Get the current user
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json([
                'success' => false,
                'message' => 'You must be logged in to test this functionality'
            ], 401);
        }
        
        try {
            // Test the password change notification
            $ipAddress = '127.0.0.1';
            $userAgent = 'Test Browser';
            
            // Directly call the notification service
            $notificationService->sendPasswordChangeNotification($user, $ipAddress, $userAgent);
            
            return $this->json([
                'success' => true,
                'message' => 'Notification email test was sent to: ' . $user->getEmail(),
                'user_id' => $user->getId_user(),
                'user_email' => $user->getEmail()
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Error sending notification: ' . $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }

    #[Route('/test-reset-notification', name: 'app_test_reset_notification')]
    public function testResetNotification(
        EntityManagerInterface $entityManager, 
        SecurityNotificationService $notificationService
    ): Response
    {
        // Get the current user
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json([
                'success' => false,
                'message' => 'You must be logged in to test this functionality'
            ], 401);
        }
        
        try {
            // Test the password reset notification
            $ipAddress = '127.0.0.1';
            $userAgent = 'Test Browser';
            
            // Directly call the reset notification service
            $notificationService->sendPasswordResetNotification($user, $ipAddress, $userAgent);
            
            return $this->json([
                'success' => true,
                'message' => 'Reset notification email test was sent to: ' . $user->getEmail(),
                'user_id' => $user->getId_user(),
                'user_email' => $user->getEmail()
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Error sending reset notification: ' . $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }

    #[Route('/test-both-notifications', name: 'app_test_both_notifications')]
    public function testBothNotifications(
        EntityManagerInterface $entityManager, 
        SecurityNotificationService $notificationService
    ): Response
    {
        // Get the current user
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json([
                'success' => false,
                'message' => 'You must be logged in to test this functionality'
            ], 401);
        }
        
        $result = [
            'user' => $user->getEmail(),
            'tests' => []
        ];
        
        try {
            // Test data
            $ipAddress = '127.0.0.1';
            $userAgent = 'Test Browser';
            
            // Test #1: Password Change notification (profile flow)
            try {
                $notificationService->sendPasswordChangeNotification($user, $ipAddress, $userAgent);
                $result['tests']['change_notification'] = [
                    'success' => true,
                    'message' => 'Change notification sent successfully'
                ];
            } catch (\Exception $e) {
                $result['tests']['change_notification'] = [
                    'success' => false,
                    'message' => 'Error: ' . $e->getMessage()
                ];
            }
            
            // Test #2: Password Reset notification (reset flow)
            try {
                $notificationService->sendPasswordResetNotification($user, $ipAddress, $userAgent);
                $result['tests']['reset_notification'] = [
                    'success' => true,
                    'message' => 'Reset notification sent successfully'
                ];
            } catch (\Exception $e) {
                $result['tests']['reset_notification'] = [
                    'success' => false,
                    'message' => 'Error: ' . $e->getMessage()
                ];
            }
            
            return $this->json($result);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Error testing notifications: ' . $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }

    #[Route('/diagnose-notifications', name: 'app_diagnose_notifications')]
    public function diagnoseNotifications(
        EntityManagerInterface $entityManager,
        SecurityNotificationService $notificationService = null,
        \App\Service\ResetPasswordService $resetPasswordService = null
    ): Response
    {
        // Get the current user
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json([
                'success' => false,
                'message' => 'You must be logged in to test this functionality'
            ], 401);
        }
        
        $diagnosticData = [
            'user' => $user->getEmail(),
            'diagnostics' => [
                'notification_service' => $notificationService !== null ? 'Available' : 'Not available',
                'reset_password_service' => $resetPasswordService !== null ? 'Available' : 'Not available',
            ],
            'services_usable' => [
                'can_use_notification_service' => false,
                'can_use_reset_service' => false,
                'notification_in_reset_service' => false
            ]
        ];
        
        // Test notification service
        if ($notificationService) {
            try {
                // Simple reflection test
                $reflection = new \ReflectionClass($notificationService);
                $diagnosticData['services_usable']['can_use_notification_service'] = true;
                $diagnosticData['diagnostics']['notification_methods'] = [
                    'sendPasswordChangeNotification' => method_exists($notificationService, 'sendPasswordChangeNotification'),
                    'sendPasswordResetNotification' => method_exists($notificationService, 'sendPasswordResetNotification')
                ];
            } catch (\Exception $e) {
                $diagnosticData['diagnostics']['notification_error'] = $e->getMessage();
            }
        }
        
        // Test reset password service
        if ($resetPasswordService) {
            try {
                // Simple reflection test
                $reflection = new \ReflectionClass($resetPasswordService);
                $diagnosticData['services_usable']['can_use_reset_service'] = true;
                $diagnosticData['diagnostics']['reset_service_methods'] = [
                    'resetPassword' => method_exists($resetPasswordService, 'resetPassword'),
                    'validateResetToken' => method_exists($resetPasswordService, 'validateResetToken')
                ];
                
                // Check if notificationService is available in resetPasswordService
                $property = $reflection->getProperty('securityNotification');
                $property->setAccessible(true);
                $value = $property->getValue($resetPasswordService);
                $diagnosticData['services_usable']['notification_in_reset_service'] = $value !== null;
                $diagnosticData['diagnostics']['notification_in_reset_service'] = $value !== null ? 'Available' : 'Not available';
            } catch (\Exception $e) {
                $diagnosticData['diagnostics']['reset_service_error'] = $e->getMessage();
            }
        }
        
        return $this->json($diagnosticData);
    }
} 