<?php

namespace App\Controller;

use App\Service\TwilioService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SmsController extends AbstractController
{
    #[Route('/send-sms', name: 'app_send_sms', methods: ['GET', 'POST'])]
    public function sendSms(Request $request, TwilioService $twilioService): Response
    {
        $sent = false;
        $error = null;
        
        if ($request->isMethod('POST')) {
            $to = $request->request->get('phone_number');
            $message = $request->request->get('message');
            
            try {
                // Validate phone number format (simple check)
                if (empty($to) || !preg_match('/^\+?[1-9]\d{1,14}$/', $to)) {
                    throw new \InvalidArgumentException('Please enter a valid phone number in E.164 format (e.g., +16175551212)');
                }
                
                // Send the SMS
                $twilioService->sendSms($to, $message);
                $sent = true;
                $this->addFlash('success', 'SMS sent successfully!');
                
            } catch (\Exception $e) {
                $error = $e->getMessage();
                $this->addFlash('error', 'Failed to send SMS: ' . $error);
            }
        }
        
        return $this->render('sms/send.html.twig', [
            'sent' => $sent,
            'error' => $error
        ]);
    }
    
    #[Route('/notify-reclamation/{id_reclamation}', name: 'app_notify_reclamation', methods: ['GET'])]
    public function notifyReclamation(int $id_reclamation, TwilioService $twilioService, EntityManagerInterface $entityManager): Response
    {
        // Find the reclamation
        $reclamation = $entityManager->getRepository(\App\Entity\Reclamation::class)->find($id_reclamation);
        
        if (!$reclamation) {
            $this->addFlash('error', 'Reclamation not found!');
            return $this->redirectToRoute('app_reclamation_list');
        }
        
        // Get the user's phone number
        $user = $reclamation->getUser();
        $phoneNumber = $user ? $user->getPhoneNumber() : null;
        
        if (!$phoneNumber) {
            $this->addFlash('error', 'User has no associated phone number!');
            return $this->redirectToRoute('app_reclamation_detail', ['id_reclamation' => $id_reclamation]);
        }
        
        try {
            // Prepare a message about the reclamation status
            $status = $reclamation->isStatus() ? 'processed' : 'received';
            $message = "Dear " . $user->getName() . ",\n\n";
            $message .= "Your reclamation titled '" . $reclamation->getTitle() . "' has been " . $status . ".\n";
            $message .= "Thank you for using WamiaGo services.";
            
            // Send SMS notification
            $twilioService->sendSms($phoneNumber, $message);
            
            $this->addFlash('success', 'SMS notification sent to user!');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Failed to send SMS: ' . $e->getMessage());
        }
        
        return $this->redirectToRoute('app_reclamation_detail', ['id_reclamation' => $id_reclamation]);
    }
}