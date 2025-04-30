<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TestController extends BaseController
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route('/test-template', name: 'app_test_template')]
    public function testTemplate(): Response
    {
        // We no longer need to set app.user since BaseController will do it
        return $this->render('admin/test.html.twig');
    }
} 