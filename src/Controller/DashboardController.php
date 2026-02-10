<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\TicketRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DashboardController extends AbstractController
{
    public function __construct(
        private readonly TicketRepository $ticketRepository
    ) {
    }

    #[Route('/dashboard', name: 'dashboard', methods: ['GET'])]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        if ($this->isGranted('ROLE_ADMIN')) {
            return $this->redirectToRoute('admin_dashboard');
        }
        if ($this->isGranted('ROLE_NGO')) {
            return $this->redirectToRoute('ngo_dashboard');
        }

        $myTicketsCount = 0;
        if ($this->getUser()) {
            $myTicketsCount = \count($this->ticketRepository->findByUser($this->getUser()));
        }

        return $this->render('dashboard/index.html.twig', [
            'my_tickets_count' => $myTicketsCount,
        ]);
    }
}
