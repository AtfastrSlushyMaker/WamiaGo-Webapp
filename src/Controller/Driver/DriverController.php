<?php

namespace App\Controller\Driver;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class DriverController extends AbstractController
{
    #[Route('/driver/space', name: 'app_driver_space')]
    public function driverSpace(UserRepository $userRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        // Check if user is logged in
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }
        
        // Check if user is a driver
        if (!$userRepository->isUserDriver($user)) {
            throw new AccessDeniedException('You must be a driver to access this page.');
        }
        
        return $this->render('driver/dashboard.html.twig', [
            'user' => $user,
        ]);
    }
} 