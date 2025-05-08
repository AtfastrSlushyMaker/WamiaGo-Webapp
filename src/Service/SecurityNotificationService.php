<?php

namespace App\Service;

use App\Entity\User;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;

class SecurityNotificationService
{
    private MailerInterface $mailer;

    public function __construct(MailerInterface $mailer)
    {
        $this->mailer = $mailer;
    }

    /**
     * Send a notification email when password is changed through profile
     */
    public function sendPasswordChangeNotification(User $user, string $ipAddress, string $userAgent): void
    {
        try {
            // Debug logging
            error_log('Starting password change notification for user: ' . $user->getEmail());
            error_log('IP Address: ' . $ipAddress);
            error_log('User Agent: ' . $userAgent);
            
            $email = (new TemplatedEmail())
                ->from(new Address('noreply@wamiago.com', 'WamiaGo Security'))
                ->to(new Address($user->getEmail(), $user->getName()))
                ->subject('Security Alert: Your Password Has Been Changed')
                ->htmlTemplate('emails/password_changed.html.twig')
                ->textTemplate('emails/password_changed.txt.twig')
                ->context([
                    'user' => $user,
                    'change_time' => new \DateTime('now', new \DateTimeZone('Africa/Tunis')),
                    'ip_address' => $ipAddress,
                    'user_agent' => $userAgent
                ]);
            
            // Debug logging
            error_log('Email object created with templates and context');
            
            $this->mailer->send($email);
            
            // Log email sent
            error_log('Password change notification email sent to: ' . $user->getEmail());
        } catch (\Exception $e) {
            // Log error but don't block the password change
            error_log('Error sending password change notification email: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());
        }
    }

    /**
     * Send a notification email when password is reset through reset flow
     */
    public function sendPasswordResetNotification(User $user, string $ipAddress, string $userAgent): void
    {
        try {
            // Debug logging
            error_log('Starting password reset notification for user: ' . $user->getEmail());
            error_log('IP Address: ' . $ipAddress);
            error_log('User Agent: ' . $userAgent);
            
            $email = (new TemplatedEmail())
                ->from(new Address('noreply@wamiago.com', 'WamiaGo Security'))
                ->to(new Address($user->getEmail(), $user->getName()))
                ->subject('Security Alert: Your Password Has Been Reset')
                ->htmlTemplate('emails/password_reset.html.twig')
                ->textTemplate('emails/password_reset.txt.twig')
                ->context([
                    'user' => $user,
                    'reset_time' => new \DateTime('now', new \DateTimeZone('Africa/Tunis')),
                    'ip_address' => $ipAddress,
                    'user_agent' => $userAgent
                ]);
            
            // Debug logging
            error_log('Reset email object created with templates and context');
            
            $this->mailer->send($email);
            
            // Log email sent
            error_log('Password reset notification email sent to: ' . $user->getEmail());
        } catch (\Exception $e) {
            // Log error but don't block the process
            error_log('Error sending password reset notification email: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());
        }
    }
} 