<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;

class ResetPasswordService
{
    private const TOKEN_EXPIRY = '+1 hour';
    private const TOKEN_SEPARATOR = '|';

    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserRepository $userRepository,
        private MailerInterface $mailer,
        private string $appSecret,
        private string $appEmail
    ) {
    }

    public function processForgotPasswordRequest(string $email): void
    {
        $user = $this->userRepository->findOneBy(['email' => $email]);
        
        if (!$user) {
            throw new UserNotFoundException();
        }

        $token = $this->generateResetToken($user);
        $this->sendResetPasswordEmail($user, $token);
    }

    public function validateResetToken(string $token): User
    {
        $parts = explode(self::TOKEN_SEPARATOR, $token);
        if (count($parts) !== 2) {
            throw new \InvalidArgumentException('Invalid token format');
        }

        [$email, $signature] = $parts;
        $expectedSignature = $this->generateSignature($email);

        if (!hash_equals($expectedSignature, $signature)) {
            throw new \InvalidArgumentException('Invalid token signature');
        }

        $user = $this->userRepository->findOneBy(['email' => $email]);
        if (!$user) {
            throw new UserNotFoundException();
        }

        return $user;
    }

    private function generateResetToken(User $user): string
    {
        $email = $user->getEmail();
        $signature = $this->generateSignature($email);
        return $email . self::TOKEN_SEPARATOR . $signature;
    }

    private function generateSignature(string $email): string
    {
        $data = $email . $this->appSecret;
        return hash_hmac('sha256', $data, $this->appSecret);
    }

    private function sendResetPasswordEmail(User $user, string $token): void
    {
        $resetUrl = $this->generateResetUrl($token);
        
        $email = (new Email())
            ->from($this->appEmail)
            ->to($user->getEmail())
            ->subject('Password Reset Request')
            ->html($this->getEmailContent($user, $resetUrl));

        $this->mailer->send($email);
    }

    private function generateResetUrl(string $token): string
    {
        return $this->generateUrl('app_reset_password', ['token' => $token]);
    }

    private function getEmailContent(User $user, string $resetUrl): string
    {
        return <<<HTML
            <h1>Password Reset Request</h1>
            <p>Hello {$user->getName()},</p>
            <p>We received a request to reset your password. Click the link below to proceed:</p>
            <p><a href="{$resetUrl}">Reset Password</a></p>
            <p>If you did not request this password reset, please ignore this email.</p>
            <p>This link will expire in 1 hour.</p>
            <p>Best regards,<br>WamiaGo Team</p>
        HTML;
    }
} 