<?php

namespace App\Controller\front;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
    public function contact(): Response
    {
        return $this->render('front/contact.html.twig', [
            'title' => 'Contact Us',
            'meta_description' => 'Get in touch with WamiaGo for inquiries, support, or feedback.'
        ]);
    }
}
