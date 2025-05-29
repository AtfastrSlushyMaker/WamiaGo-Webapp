<?php

namespace App\Controller;

use App\Entity\Reclamation;
use App\Entity\Response;
use App\Form\ResponseType;
use App\Repository\ResponseRepository;
use App\Service\TwilioService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/admin/response')]
final class ResponseController extends AbstractController
{
    #[Route(name: 'app_response_index', methods: ['GET'])]
    public function index(ResponseRepository $responseRepository): HttpResponse
    {
        return $this->render('response/index.html.twig', [
            'responses' => $responseRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_response_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, ValidatorInterface $validator): HttpResponse
    {
        $response = new Response();
        $response->setDate(new \DateTime()); // Set current date as default
        
        $form = $this->createForm(ResponseType::class, $response);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            // Validate the entity
            $errors = $validator->validate($response);
            
            if (count($errors) === 0) {
                // If no validation errors, save the entity
                $entityManager->persist($response);
                $entityManager->flush();

                $this->addFlash('success', 'Response has been created successfully.');
                return $this->redirectToRoute('app_response_index', [], HttpResponse::HTTP_SEE_OTHER);
            } else {
                // If there are validation errors, add flash messages
                foreach ($errors as $error) {
                    $this->addFlash('error', $error->getMessage());
                }
                
                // For debugging
                $this->addFlash('error', 'Validation failed. Please check your input and try again.');
            }
        }

        return $this->render('response/new.html.twig', [
            'response' => $response,
            'form' => $form,
        ]);
    }

    #[Route('/{id_response}', name: 'app_response_show', methods: ['GET'])]
    public function show(Response $response): HttpResponse
    {
        return $this->render('response/show.html.twig', [
            'response' => $response,
        ]);
    }

    #[Route('/{id_response}/edit', name: 'app_response_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Response $response, EntityManagerInterface $entityManager, ValidatorInterface $validator): HttpResponse
    {
        $form = $this->createForm(ResponseType::class, $response);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            // Validate the entity
            $errors = $validator->validate($response);
            
            if (count($errors) === 0) {
                $entityManager->flush();
                $this->addFlash('success', 'Response has been updated successfully.');
                return $this->redirectToRoute('app_response_index', [], HttpResponse::HTTP_SEE_OTHER);
            } else {
                // If there are validation errors, add flash messages
                foreach ($errors as $error) {
                    $this->addFlash('error', $error->getMessage());
                }
                
                // For debugging
                $this->addFlash('error', 'Validation failed. Please check your input and try again.');
            }
        }

        return $this->render('response/edit.html.twig', [
            'response' => $response,
            'form' => $form,
        ]);
    }

    #[Route('/{id_response}', name: 'app_response_delete', methods: ['POST'])]
    public function delete(Request $request, Response $response, EntityManagerInterface $entityManager): HttpResponse
    {
        if ($this->isCsrfTokenValid('delete'.$response->getId_response(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($response);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_response_index', [], HttpResponse::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/update-status', name: 'app_response_update_status', methods: ['POST'])]
    public function updateReponseStatus(int $id, EntityManagerInterface $entityManager): HttpResponse
    {
        $reclamation = $entityManager->getRepository(Reclamation::class)->find($id);
            $reclamation->setStatus(1);
            $entityManager->persist($reclamation);
            $entityManager->flush();
        

        return $this->redirectToRoute('app_reclamation_list', [], HttpResponse::HTTP_SEE_OTHER);
    }
 
    #[Route('/new/reclamation/{id_reclamation}', name: 'app_response_new_for_reclamation', methods: ['POST'])]
    public function newForReclamation(
        Request $request, 
        EntityManagerInterface $entityManager, 
        int $id_reclamation,
        MailerInterface $mailer,
        ValidatorInterface $validator,
        TwilioService $twilioService
    ): HttpResponse
    {
        $reclamation = $entityManager->getRepository(Reclamation::class)->find($id_reclamation);
        
        if (!$reclamation) {
            throw $this->createNotFoundException('Reclamation not found');
        }
        
        // Validate CSRF token
        $submittedToken = $request->request->get('token');
        if (!$this->isCsrfTokenValid('response_form', $submittedToken)) {
            $this->addFlash('error', 'Invalid CSRF token');
            return $this->redirectToRoute('app_reclamation_detail', ['id_reclamation' => $id_reclamation]);
        }
        
        $responseData = $request->request->all('response');
        $content = trim($responseData['content'] ?? '');
        
        // Check if content is empty
        if (empty($content)) {
            $this->addFlash('error', 'Le message ne peut pas être vide');
            return $this->redirectToRoute('app_reclamation_detail', ['id_reclamation' => $id_reclamation]);
        }
        
        // Check if content is too short
        if (strlen($content) < 10) {
            $this->addFlash('error', 'Le message doit contenir au moins 10 caractères');
            return $this->redirectToRoute('app_reclamation_detail', ['id_reclamation' => $id_reclamation]);
        }
        
        // Create and persist the response
        $response = new Response();
        $response->setContent($content);
        $response->setReclamation($reclamation);
        $response->setDate(new \DateTime());
        
        // Validate the entity using validators defined in the entity
        $errors = $validator->validate($response);
        if (count($errors) > 0) {
            foreach ($errors as $error) {
                $this->addFlash('error', $error->getMessage());
            }
            return $this->redirectToRoute('app_reclamation_detail', ['id_reclamation' => $id_reclamation]);
        }
        
        $entityManager->persist($response);
        $entityManager->flush();
        
        // Get the user from the reclamation
        $user = $reclamation->getUser();
        
        // Send email to the user
        if ($user && $user->getEmail()) {
            $email = (new Email())
                ->from('walikghrairi@gmail.com')
                ->to($user->getEmail())
                ->subject('Réponse à votre réclamation - WamiaGo')
                ->html($this->renderView(
                    'response/email.html.twig',
                    [
                        'reclamation' => $reclamation,
                        'response' => $response,
                        'user' => $user
                    ]
                ));
            
            try {
                $mailer->send($email);
            } catch (\Exception $e) {
               // Log the error but continue
               $this->addFlash('warning', 'La réponse a été enregistrée mais l\'email n\'a pas pu être envoyé.');
            }
        }

        // Send SMS to the user
        if ($user) {
            try {
                // Get phone number directly from the user entity
                $phoneNumber = $user->getPhone_number();
                
                // Make sure we have a phone number
                if (!empty($phoneNumber)) {
                    // Only clean up non-numeric characters
                    $cleanedPhoneNumber = preg_replace('/[^\d]/', '', $phoneNumber);
                    
                    // Ensure E.164 format for Twilio (with + and country code)
                    if (!preg_match('/^\+/', $cleanedPhoneNumber)) {
                        // Add Tunisia country code if it's not already there
                        if (!preg_match('/^216/', $cleanedPhoneNumber)) {
                            $cleanedPhoneNumber = '+216' . $cleanedPhoneNumber;
                        } else {
                            // If it already has the country code but no +
                            $cleanedPhoneNumber = '+' . $cleanedPhoneNumber;
                        }
                    }
                    
                    // Send SMS notification with the properly formatted number
                    $twilioService->sendSms(
                        $cleanedPhoneNumber,
                        'Vous avez une nouvelle réponse à votre réclamation. Veuillez vérifier votre email pour plus de détails.'
                    );
                    
                    // Add a flash message indicating SMS was sent
                    $this->addFlash('info', 'Un SMS de notification a été envoyé au numéro ' . $phoneNumber . ' (formaté en ' . $cleanedPhoneNumber . ')');
                } else {
                    $this->addFlash('warning', 'Impossible d\'envoyer un SMS: numéro de téléphone manquant.');
                }
            } catch (\Exception $e) {
                $errorMessage = $e->getMessage();
                // Log the actual error for debugging
                error_log('Twilio SMS error: ' . $errorMessage);
                
                // More specific error messages
                if (strpos($errorMessage, 'unverified') !== false && strpos($errorMessage, 'Trial accounts') !== false) {
                    $this->addFlash('warning', 'SMS non envoyé: Votre compte Twilio est un compte d\'essai. Vous devez vérifier le numéro du destinataire dans votre tableau de bord Twilio.');
                } else {
                    // General error
                    $this->addFlash('warning', 'La réponse a été enregistrée mais le SMS n\'a pas pu être envoyé: ' . $errorMessage);
                }
            }
        }
        
        $this->addFlash('success', 'Votre réponse a été enregistrée avec succès et un email a été envoyé à l\'utilisateur.');
        return $this->redirectToRoute('app_reclamation_detail', ['id_reclamation' => $id_reclamation]);
    }
}
