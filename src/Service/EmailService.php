<?php

namespace App\Service;

use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Psr\Log\LoggerInterface;

class EmailService
{
    public function __construct(
        private MailerInterface $mailer,
        private LoggerInterface $logger,
        private string $fromEmail = 'azer.ab.rougui@gmail.com',
        private string $fromName = 'WamiaGo Transport'
    ) {}

    public function sendReservationNotificationToTransporter(
        \DateTimeInterface $date,
        ?string $description,
        string $startLocation,
        string $endLocation,
        string $userName,
        string $announcementTitle,
        string $transporterEmail,
        string $transporterName
    ): void {
        try {
            $this->logger->info('Preparing to send email notification to ' . $transporterEmail);
            
            $email = (new TemplatedEmail())
                ->from(new Address($this->fromEmail, $this->fromName))
                ->to(new Address($transporterEmail, $transporterName))
                ->subject('New Transportation Reservation - WamiaGo')
    
                ->htmlTemplate('emails/reservation_notification.html.twig')
                ->context([
                    'transporterName' => 'Driver',
                    'reservationDate' => $date,
                    'description' => $description ?? 'No description provided',
                    'startLocation' => $startLocation,
                    'endLocation' => $endLocation,
                    'clientName' => $userName,
                    'announcementTitle' => $announcementTitle
                ]);

            $this->mailer->send($email);
            $this->logger->info('Email sent successfully');
        } catch (\Exception $e) {
            $this->logger->error('Failed to send email: ' . $e->getMessage());
            throw $e;
        }
    }
}