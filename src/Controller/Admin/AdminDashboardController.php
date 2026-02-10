<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Repository\TicketRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class AdminDashboardController extends AbstractController
{
    public function __construct(
        private readonly TicketRepository $ticketRepository
    ) {
    }

    #[Route('', name: 'admin_dashboard', methods: ['GET'])]
    public function index(): Response
    {
        $pendingTicketsCount = \count($this->ticketRepository->findPendingForAdmin());
        $pendingCompletionsCount = \count($this->ticketRepository->findPendingCompletions());

        return $this->render('admin/dashboard.html.twig', [
            'pending_tickets_count' => $pendingTicketsCount,
            'pending_completions_count' => $pendingCompletionsCount,
        ]);
    }
}
