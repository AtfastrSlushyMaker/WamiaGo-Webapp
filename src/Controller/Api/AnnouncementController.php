<?php

namespace App\Controller\Api;

use App\Repository\AnnouncementRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Knp\Component\Pager\PaginatorInterface;

#[Route('/api')]
class AnnouncementController extends AbstractController
{
    #[Route('/announcements/filter', name: 'api_announcements_filter', methods: ['GET'])]
    public function filter(
        Request $request,
        AnnouncementRepository $repository,
        PaginatorInterface $paginator
    ): Response {
        // Get filter parameters
        $keyword = $request->query->get('keyword');
        $zone = $request->query->get('zone');
        $date = $request->query->get('date');

        // Get filtered query from repository
        $query = $repository->createFilteredQuery($keyword, $zone, $date);

        // Paginate results
        $announcements = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            9
        );

        // Render only the announcements list partial
        return $this->render('front/announcement/_announcement_list.html.twig', [
            'announcements' => $announcements
        ]);
    }
}