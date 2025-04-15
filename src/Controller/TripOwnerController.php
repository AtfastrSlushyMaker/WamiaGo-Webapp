<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class TripOwnerController extends AbstractController
{
    #[Route('/trip/owner', name: 'app_trip_owner')]
    public function index(): Response
    {
        return $this->render('trip_owner/index.html.twig', [
            'controller_name' => 'TripOwnerController',
        ]);
    }
}
