<?php

namespace App\Controller\front;

use App\Entity\Reclamation;
use App\Entity\User;
use App\Form\ReclamationType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class FrontOfficeController extends AbstractController
{
    #[Route('/', name: 'app_front_home')]
    public function index(): Response
    {
        return $this->render('front/index.html.twig', [
            'title' => 'Welcome to WamiaGo',
            'meta_description' => 'WamiaGo provides ride sharing, bicycle rentals, and transportation news for your community.'
        ]);
    }

    #[Route('/about', name: 'app_front_about')]
    public function about(): Response
    {
        return $this->render('front/about.html.twig', [
            'title' => 'About Us',
            'meta_description' => 'Learn about WamiaGo\'s mission, vision, and the team behind our transportation solutions.'
        ]);
    }

    #[Route('/contact', name: 'app_front_contact')]
    public function contact(Request $request, EntityManagerInterface $entityManager): Response
    {
        // Create a new reclamation
        $reclamation = new Reclamation();
        
        // Try to get user with ID 1 or create a temporary user if needed
        $userRepository = $entityManager->getRepository(User::class);
        $user = $userRepository->find(1);
        
        // If user doesn't exist, we'll create one temporarily or use null
        if (!$user) {
            // For demonstration/development only - in production, you'd want to use a real user
            // This is to prevent failures due to missing user association
            $user = new User();
            // Set minimum required fields for a valid user
            $user->setEmail('demo@wamiango.com');
            $user->setName('Demo User');
            $user->setPassword('password123'); // Not secure, just for demo
            $user->setPhoneNumber('1234567890');
            $user->setRole('ROLE_USER');
            $user->setGender('Unspecified');
            $user->setStatus('ACTIVE');
            $user->setAccountStatus('ACTIVE');
            $user->setIsVerified(true);
            
            // Persist this temporary user
            $entityManager->persist($user);
            $entityManager->flush();
        }
        
        // Set the user (which now should exist)
        $reclamation->setUser($user);
        
        // Set initial values
        $reclamation->setDate(new \DateTime());
        $reclamation->setStatus(false);
        
        // Create the form
        $form = $this->createForm(ReclamationType::class, $reclamation);
        
        // Handle form submission
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                // Ensure we have the necessary fields set
                if (!$reclamation->getTitle() || !$reclamation->getContent()) {
                    throw new \Exception('Title and content are required');
                }
                
                // Double check date is set
                if (!$reclamation->getDate()) {
                    $reclamation->setDate(new \DateTime());
                }
                
                // Double check status is set
                if ($reclamation->isStatus() === null) {
                    $reclamation->setStatus(false);
                }
                
                // Debug information - showing this to help troubleshoot
                $this->addFlash('info', 'Debug: Title: ' . $reclamation->getTitle() . 
                    ' | Content: ' . substr($reclamation->getContent(), 0, 20) . '...' .
                    ' | User ID: ' . ($reclamation->getUser() ? $reclamation->getUser()->getId_user() : 'null'));
                
                // Persist and save the reclamation
                $entityManager->persist($reclamation);
                $entityManager->flush();
                
                $this->addFlash('success', 'Your reclamation has been submitted successfully!');
                
                // Redirect to avoid form resubmission
                return $this->redirectToRoute('app_front_contact');
                
            } catch (\Exception $e) {
                // Log the error for debugging
                $this->addFlash('error', 'An error occurred: ' . $e->getMessage());
                // More detailed error info
                $this->addFlash('error', 'Error details: ' . get_class($e) . ' at line ' . $e->getLine());
            }
        } elseif ($form->isSubmitted()) {
            // If submitted but not valid, show form errors
            $errors = $form->getErrors(true);
            foreach ($errors as $error) {
                $this->addFlash('error', $error->getMessage());
            }
            $this->addFlash('error', 'Please check your submission. There were validation errors.');
        }
        
        return $this->render('front/contact.html.twig', [
            'title' => 'Contact Us',
            'meta_description' => 'Get in touch with WamiaGo for inquiries, support, or feedback.',
            'form' => $form->createView(),
        ]);
    }
}
