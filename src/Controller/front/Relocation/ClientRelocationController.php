<?php

namespace App\Controller\front\Relocation;

use App\Entity\Relocation;
use App\Repository\RelocationRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/client/relocations')]
class ClientRelocationController extends AbstractController
{
    private const HARDCODED_CLIENT_ID = 115;

    public function __construct(
        private EntityManagerInterface $em,
        private RelocationRepository $relocationRepo
    ) {}

    #[Route('/', name: 'app_client_relocation_list', methods: ['GET'])]
    public function list(UserRepository $userRepo): Response
    {
        $client = $userRepo->find(self::HARDCODED_CLIENT_ID);
        
        if (!$client) {
            throw $this->createNotFoundException('Client not found');
        }

        $relocations = $this->relocationRepo->findByClient($client);

        return $this->render('front/relocation/client/list.html.twig', [
            'relocations' => $relocations
        ]);
    }

    #[Route('/{id}/details', name: 'app_client_relocation_details', methods: ['GET'])]
    public function details(Relocation $relocation): JsonResponse
    {
        $transporter = $relocation->getReservation()->getAnnouncement()->getDriver();
        
        return $this->json([
            'reservationTitle' => $relocation->getReservation()->getAnnouncement()->getTitle(),
            'transporterName' => $transporter->getUser()->getName(),
            'transporterPhone' => $transporter->getUser()->getPhoneNumber(),
            'date' => $relocation->getDate()->format('d M Y, H:i'),
            'cost' => $relocation->getCost(),
            'status' => $relocation->isStatus() ? 'Active' : 'Inactive',
            'startLocation' => $relocation->getReservation()->getStartLocation()->getAddress(),
            'endLocation' => $relocation->getReservation()->getEndLocation()->getAddress()
        ]);
    }

    #[Route('/{id}/delete', name: 'app_client_relocation_delete', methods: ['POST'])]
public function delete(Relocation $relocation): JsonResponse
{
    try {
        $this->em->remove($relocation);
        $this->em->flush();

        return $this->json([
            'success' => true,
            'message' => 'Relocation deleted successfully'
        ]);
    } catch (\Exception $e) {
        return $this->json([
            'success' => false,
            'message' => 'Error deleting relocation: ' . $e->getMessage()
        ], 500);
    }
}
}