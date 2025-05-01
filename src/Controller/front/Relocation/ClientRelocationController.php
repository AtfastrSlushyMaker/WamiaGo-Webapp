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
use Knp\Component\Pager\PaginatorInterface;
use App\Enum\ReservationStatus;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Security\Core\Security;

#[Route('/client/relocations')]
class ClientRelocationController extends AbstractController
{
    //private const HARDCODED_CLIENT_ID = 122;

    public function __construct(
        private EntityManagerInterface $em,
        private RelocationRepository $relocationRepo,
        private UserRepository $userRepo,
        private readonly Security $security
    ) {}

    #[Route('/', name: 'app_client_relocation_list', methods: ['GET'])]
    public function list(Request $request, PaginatorInterface $paginator): Response
    {
        // Get the client user - in a real app, you'd use $this->getUser() for the authenticated user
        // But since you're using a hardcoded ID for now:
        $client = $this->security->getUser();
        
        if (!$client) {
            throw $this->createNotFoundException('User not found');
        }
        
        $filters = [
            'keyword' => $request->query->get('keyword'),
            'date' => $request->query->get('date')
        ];

        // Use the repository method to get QueryBuilder with filters
        $queryBuilder = $this->relocationRepo->createClientSearchQueryBuilder($client, $filters);
        
        $relocations = $paginator->paginate(
            $queryBuilder->getQuery(),
            $request->query->getInt('page', 1),
            4
        );

        // Handle AJAX request
        if ($request->isXmlHttpRequest()) {
            return $this->render('front/relocation/client/_relocation_list.html.twig', [
                'relocations' => $relocations
            ]);
        }

        return $this->render('front/relocation/client/list.html.twig', [
            'relocations' => $relocations
        ]);
    }

    #[Route('/{id}/details', name: 'app_client_relocation_details', methods: ['GET'])]
public function details(Relocation $relocation): JsonResponse
{
    // Obtenez l'utilisateur courant
    $currentUser = $this->security->getUser();
    
    // Vérifiez si l'utilisateur actuel est le propriétaire de cette relocation
    // Assumant que getIdUser() est la méthode pour obtenir l'ID utilisateur
    /*if ($relocation->getReservation()->getUser()->getIdUser() !== $currentUser->getIdUser()) {
        throw $this->createAccessDeniedException('Access denied to this relocation');
    }*/
    
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
    /** @var User $user */
    $user = $this->security->getUser();
    
    // Vérification d'authentification
    if (!$user) {
        return $this->json([
            'success' => false,
            'message' => 'Authentication required'
        ], 401);
    }

    // Vérification de propriété
    if ($relocation->getReservation()->getUser()->getIdUser() !== $user->getIdUser()) {
        return $this->json([
            'success' => false,
            'message' => 'Access denied to this relocation'
        ], 403);
    }

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