<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Ticket;
use App\Form\TicketType;
use App\Repository\TicketRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/tickets')]
#[IsGranted('ROLE_ADMIN')]
class AdminTicketController extends AbstractController
{
    public function __construct(
        private readonly TicketRepository $ticketRepository,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    #[Route('', name: 'admin_ticket_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('admin/ticket/index.html.twig', [
            'tickets' => $this->ticketRepository->findBy([], ['createdAt' => 'DESC']),
        ]);
    }

    #[Route('/{id}/show', name: 'admin_ticket_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(Ticket $ticket): Response
    {
        return $this->render('admin/ticket/show.html.twig', [
            'ticket' => $ticket,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_ticket_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(Request $request, Ticket $ticket): Response
    {
        $form = $this->createForm(TicketType::class, $ticket, [
            'is_admin' => true
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();
            $this->addFlash('success', 'Ticket updated successfully.');

            return $this->redirectToRoute('admin_ticket_index');
        }

        return $this->render('admin/ticket/edit.html.twig', [
            'ticket' => $ticket,
            'form' => $form,
        ]);
    }
}
