<?php

namespace App\Controller;

use App\Entity\Response;
use App\Form\ResponseType;
use App\Repository\ResponseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/response')]
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
    public function new(Request $request, EntityManagerInterface $entityManager): HttpResponse
    {
        $response = new Response();
        $form = $this->createForm(ResponseType::class, $response);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($response);
            $entityManager->flush();

            return $this->redirectToRoute('app_response_index', [], HttpResponse::HTTP_SEE_OTHER);
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
    public function edit(Request $request, Response $response, EntityManagerInterface $entityManager): HttpResponse
    {
        $form = $this->createForm(ResponseType::class, $response);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_response_index', [], HttpResponse::HTTP_SEE_OTHER);
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
}
