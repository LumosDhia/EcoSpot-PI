<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Ticket;
use App\Entity\NgoAssignmentRequest;
use App\Enum\TicketStatus;
use App\Form\TicketType;
use App\Repository\TicketRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

#[Route('/admin/tickets')]
#[IsGranted('ROLE_ADMIN')]
class AdminTicketController extends AbstractController
{
    public function __construct(
        private readonly TicketRepository $ticketRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly PaginatorInterface $paginator
    ) {
    }

    #[Route('', name: 'admin_ticket_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $query = $request->query->getString('q');
        $query = $query === '' ? null : $query;
        $sortBy = $request->query->getString('sort', 'newest');

        $ticketsQuery = $this->ticketRepository->getQueryAllForAdmin($query, $sortBy);
        
        $pagination = $this->paginator->paginate(
            $ticketsQuery,
            $request->query->getInt('page', 1),
            10
        );

        return $this->render('admin/ticket/index.html.twig', [
            'tickets' => $pagination,
            'currentQuery' => $query,
            'currentSort' => $sortBy,
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
            $this->handleTicketImage($form, $ticket);
            $this->entityManager->flush();
            $this->addFlash('success', 'Ticket updated successfully.');

            return $this->redirectToRoute('admin_ticket_index');
        }

        return $this->render('admin/ticket/edit.html.twig', [
            'ticket' => $ticket,
            'form' => $form,
        ]);
    }

    private function handleTicketImage(FormInterface $form, Ticket $ticket): void
    {
        $file = $form->get('imageFile')->getData();
        if (!$file || !$file->isValid()) {
            return;
        }
        $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($file->getMimeType(), $allowed, true)) {
            return;
        }
        /** @var string $kernelProjectDir */
        $kernelProjectDir = $this->getParameter('kernel.project_dir');
        $dir = $kernelProjectDir . '/public/uploads/tickets';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $safeName = uniqid('ticket_', true) . '.' . ($file->guessExtension() ?: 'jpg');
        try {
            $file->move($dir, $safeName);
            $ticket->setImage('/uploads/tickets/' . $safeName);
        } catch (FileException $e) {
            // leave ticket.image unchanged
        }
    }
}
