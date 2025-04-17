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
        
        // For development, we'll use a fixed user ID
        // In a real app, you would use the authenticated user
        $userRepository = $entityManager->getRepository(User::class);
        $user = $userRepository->find(1);
        
        if ($user) {
            $reclamation->setUser($user);
        }
        
        // Set initial values
        $reclamation->setDate(new \DateTime());
        $reclamation->setStatus(false);
        
        // Create the form
        $form = $this->createForm(ReclamationType::class, $reclamation);
        
        // Handle form submission
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($reclamation);
            $entityManager->flush();
            
            $this->addFlash('success', 'Your reclamation has been submitted successfully!');
            
            // Redirect to avoid form resubmission
            return $this->redirectToRoute('app_front_contact');
        }
        
        return $this->render('front/contact.html.twig', [
            'title' => 'Contact Us',
            'meta_description' => 'Get in touch with WamiaGo for inquiries, support, or feedback.',
            'form' => $form->createView(),
        ]);
    }
}
