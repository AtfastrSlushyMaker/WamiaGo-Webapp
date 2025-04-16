<?php

namespace App\Controller;

use App\Entity\Reclamation;
use App\Form\ReclamationType;
use App\Repository\ReclamationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\User;

#[Route('/reclamation')]
final class ReclamationController extends AbstractController
{
    #[Route('/{id_reclamation}', name: 'app_reclamation_delete', methods: ['POST'])]
    public function deleteRec(Request $request, Reclamation $reclamation, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$reclamation->getId_reclamation(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($reclamation);
            $entityManager->flush();
        }
    
        return $this->redirectToRoute('app_reclamation_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id_reclamation}/detail', name: 'app_reclamation_detail', methods: ['GET'])]
    public function detail(Reclamation $reclamation): Response
    {
        return $this->render('reclamation/detail.html.twig', [
            'reclamation' => $reclamation,
        ]);
    }
    
    
    #[Route('/getAllReclamation', name: 'app_reclamation_list', methods: ['GET'])]
    public function getAllReclamation(ReclamationRepository $reclamationRepository): Response
    {
        return $this->render('reclamation/index.html.twig', [
            'reclamations' => $reclamationRepository->findAll(),
        ]);
    }

    #[Route('/', name: 'app_reclamation_index', methods: ['GET'])]
    public function index(): Response
    {
        // Redirige vers la route qui liste les réclamations
        return $this->redirectToRoute('app_reclamation_list');
    }

    #[Route('/new', name: 'app_reclamation_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $reclamation = new Reclamation();
        $form = $this->createForm(ReclamationType::class, $reclamation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($reclamation);
            $entityManager->flush();

            return $this->redirectToRoute('app_reclamation_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('reclamation/new.html.twig', [
            'reclamation' => $reclamation,
            'form' => $form,
        ]);
    }

    #[Route('/create', name: 'reclamation_create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $entityManager): Response
    {
        // Since we don't have session implementation, we'll use a fixed user ID
        // Fetch the user from the database
        $userRepository = $entityManager->getRepository(User::class);
        $user = $userRepository->find(1);

        if (!$user) {
            throw $this->createNotFoundException('User with ID 1 not found');
        }
        
        $reclamation = new Reclamation();
        $reclamation->setUser($user);
        $reclamation->setTitle($request->request->get('title'));
        $reclamation->setContent($request->request->get('content'));
        $reclamation->setDate(new \DateTime());
        $reclamation->setStatus(false);

        $entityManager->persist($reclamation);
        $entityManager->flush();

        $this->addFlash('success', 'Your message has been sent successfully!');
        return $this->redirectToRoute('app_front_home');
    }

    #[Route('/{id_reclamation}/edit', name: 'app_reclamation_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Reclamation $reclamation, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ReclamationType::class, $reclamation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_reclamation_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('reclamation/edit.html.twig', [
            'reclamation' => $reclamation,
            'form' => $form,
        ]);
    }

    #[Route('/{id_reclamation}', name: 'app_reclamation_delete', methods: ['POST'])]
    public function delete(Request $request, Reclamation $reclamation, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$reclamation->getId_reclamation(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($reclamation);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_reclamation_index', [], Response::HTTP_SEE_OTHER);
    }
}