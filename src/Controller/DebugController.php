<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

class DebugController extends AbstractController
{
    #[Route('/admin/debug/user-management', name: 'admin_debug_user_management')]
    public function userManagementDebug(): Response
    {
        return $this->render('admin/debug/user_management.html.twig', [
            'title' => 'User Management Debug',
        ]);
    }
}
