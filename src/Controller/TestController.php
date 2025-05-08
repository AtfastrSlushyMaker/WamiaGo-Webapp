<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TestController extends AbstractController
{
    #[Route('/test', name: 'app_test')]
    public function index(): Response
    {
        return new Response(
            '<html><body>
                <h1>Routing Test Page</h1>
                <p>This route is working correctly.</p>
                <p>Try these links:</p>
                <ul>
                    <li><a href="/profile">Go to Profile</a></li>
                    <li><a href="/profile/">Go to Profile (with trailing slash)</a></li>
                    <li><a href="/test">This Test Page</a></li>
                </ul>
            </body></html>'
        );
    }
} 