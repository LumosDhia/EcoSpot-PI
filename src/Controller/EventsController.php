<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Evenement;
use App\Repository\EvenementRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class EventsController extends AbstractController
{
    public function __construct(
        private readonly EvenementRepository $evenementRepository
    ) {
    }

    #[Route('/events', name: 'events_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $search = $request->query->get('q');
        $search = $search === null ? null : trim($search);
        $search = $search === '' ? null : $search;

        $events = $this->evenementRepository->searchOrderedByDate($search);

        return $this->render('events/index.html.twig', [
            'events' => $events,
            'search' => $search ?? '',
        ]);
    }

    #[Route('/events/search', name: 'events_search', methods: ['GET'], priority: 10)]
    public function search(Request $request): JsonResponse
    {
        $q = $request->query->get('q');
        $q = $q === null ? null : trim((string) $q);
        $q = $q === '' ? null : $q;

        $events = $this->evenementRepository->searchOrderedByDate($q);

        $data = array_map(function (Evenement $e) {
            $desc = $e->getDescription() ?? '';
            $description = \mb_strlen($desc) > 120 ? \mb_substr($desc, 0, 120) . '...' : $desc;
            return [
                'id' => $e->getId(),
                'nom' => $e->getNom(),
                'description' => $description,
                'lieu' => $e->getLieu(),
                'dateDebut' => $e->getDateDebut()?->format('d M Y') ?? '—',
                'capacite' => $e->getCapacite(),
                'image' => $e->getImage(),
                'sponsorsCount' => $e->getSponsors()->count(),
                'showUrl' => $this->generateUrl('events_show', ['id' => $e->getId()]),
            ];
        }, $events);

        return new JsonResponse(['events' => $data]);
    }

    #[Route('/events/{id}', name: 'events_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(Evenement $event): Response
    {
        return $this->render('events/show.html.twig', [
            'event' => $event,
        ]);
    }
}
