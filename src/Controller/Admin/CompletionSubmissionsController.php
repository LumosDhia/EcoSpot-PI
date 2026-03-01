<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Ticket;
use App\Repository\TicketRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/completions')]
#[IsGranted('ROLE_ADMIN')]
class CompletionSubmissionsController extends AbstractController
{
    public function __construct(
        private readonly TicketRepository $ticketRepository
    ) {
    }

    #[Route('', name: 'admin_completions_index', methods: ['GET'])]
    public function index(): Response
    {
        $tickets = $this->ticketRepository->findPendingCompletions();

        return $this->render('admin/completions/index.html.twig', [
            'tickets' => $tickets,
        ]);
    }

    #[Route('/{id}/achieve', name: 'admin_completion_achieve', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function achieve(Request $request, Ticket $ticket): Response
    {
        if (!$ticket->hasCompletionSubmitted() || $ticket->isAchieved()) {
            $this->addFlash('error', 'Invalid ticket.');
            return $this->redirectToRoute('admin_completions_index');
        }

        if (!$this->isCsrfTokenValid('completion-achieve-' . $ticket->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid security token.');
            return $this->redirectToRoute('admin_completions_index');
        }

        $ticket->markAsAchieved();
        $this->ticketRepository->save($ticket);
        $this->addFlash('success', 'Ticket marked as achieved.');

        return $this->redirectToRoute('admin_completions_index');
    }
}
