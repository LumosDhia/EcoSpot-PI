<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Ticket;
use App\Enum\TicketStatus;
use App\Repository\TicketRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/pending-tickets')]
#[IsGranted('ROLE_ADMIN')]
class PendingTicketsController extends AbstractController
{
    public function __construct(
        private readonly TicketRepository $ticketRepository,
        private readonly \App\Service\NotificationService $notificationService
    ) {
    }

    #[Route('', name: 'admin_pending_tickets', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $query = $request->query->getString('q');
        $query = $query === '' ? null : $query;
        $sortBy = $request->query->getString('sort', 'newest');

        $tickets = $this->ticketRepository->findPendingForAdmin($query, $sortBy);

        return $this->render('admin/pending_tickets/index.html.twig', [
            'tickets' => $tickets,
            'currentQuery' => $query,
            'currentSort' => $sortBy,
        ]);
    }

    #[Route('/{id}/publish', name: 'admin_pending_ticket_publish', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function publish(Request $request, Ticket $ticket): Response
    {
        if (!$this->isStatusPendingOrSentBack($ticket)) {
            $this->addFlash('error', 'This ticket is not pending.');
            return $this->redirectToRoute('admin_pending_tickets');
        }

        if (!$this->isCsrfTokenValid('pending-ticket-publish-' . $ticket->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid security token.');
            return $this->redirectToRoute('admin_pending_tickets');
        }

        $ticket->setStatus(TicketStatus::PUBLISHED);
        $ticket->setAdminNotes(null);
        $this->ticketRepository->save($ticket);

        $this->notificationService->notify(
            $ticket->getUser(),
            sprintf('Your ticket "%s" has been approved and published!', $ticket->getTitle()),
            'success',
            $ticket->getId()
        );

        $this->addFlash('success', 'Ticket published. It is now visible to everyone.');

        return $this->redirectToRoute('admin_pending_tickets');
    }

    #[Route('/{id}/refuse', name: 'admin_pending_ticket_refuse', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function refuse(Request $request, Ticket $ticket): Response
    {
        if (!$this->isStatusPendingOrSentBack($ticket)) {
            $this->addFlash('error', 'This ticket is not pending.');
            return $this->redirectToRoute('admin_pending_tickets');
        }

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('pending-ticket-refuse-' . $ticket->getId(), (string) $request->request->get('_token'))) {
                $this->addFlash('error', 'Invalid security token.');
                return $this->redirectToRoute('admin_pending_tickets');
            }
            $note = $request->request->getString('note', '');
            $ticket->setAdminNotes($note !== '' ? $note : null);
            $ticket->setStatus(TicketStatus::REFUSED);
            
            // Optional: Put user in timeout if marked as spam
            if ($request->request->get('spam_timeout')) {
                $ticket->getUser()->setTimeoutUntil(new \DateTimeImmutable('+24 hours'));
                
                $this->notificationService->notify(
                    $ticket->getUser(),
                    'Your account has been put in a 24-hour timeout by an administrator.',
                    'danger'
                );

                $this->addFlash('warning', 'User has been put in a 24-hour timeout.');
            }

            $this->ticketRepository->save($ticket);

            $this->notificationService->notify(
                $ticket->getUser(),
                sprintf('Your ticket "%s" has been refused.', $ticket->getTitle()),
                'danger',
                $ticket->getId()
            );

            $this->addFlash('success', 'Ticket refused.');
            return $this->redirectToRoute('admin_pending_tickets');
        }

        return $this->render('admin/pending_tickets/refuse.html.twig', [
            'ticket' => $ticket,
        ]);
    }

    #[Route('/{id}/send-back', name: 'admin_pending_ticket_send_back', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function sendBack(Request $request, Ticket $ticket): Response
    {
        if (!$this->isStatusPendingOrSentBack($ticket)) {
            $this->addFlash('error', 'This ticket is not pending.');
            return $this->redirectToRoute('admin_pending_tickets');
        }

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('pending-ticket-sendback-' . $ticket->getId(), (string) $request->request->get('_token'))) {
                $this->addFlash('error', 'Invalid security token.');
                return $this->redirectToRoute('admin_pending_tickets');
            }
            $note = $request->request->getString('note', '');
            if ($note === '') {
                $this->addFlash('error', 'Please provide a note for the user.');
                return $this->render('admin/pending_tickets/send_back.html.twig', ['ticket' => $ticket]);
            }
            $ticket->setAdminNotes($note);
            $ticket->setStatus(TicketStatus::SENT_BACK);
            $this->ticketRepository->save($ticket);

            $this->notificationService->notify(
                $ticket->getUser(),
                sprintf('Your ticket "%s" was sent back for modification. Please check the admin notes.', $ticket->getTitle()),
                'warning',
                $ticket->getId()
            );

            $this->addFlash('success', 'Ticket sent back to the user with your note.');
            return $this->redirectToRoute('admin_pending_tickets');
        }

        return $this->render('admin/pending_tickets/send_back.html.twig', [
            'ticket' => $ticket,
        ]);
    }

    private function isStatusPendingOrSentBack(Ticket $ticket): bool
    {
        $s = $ticket->getStatus();
        return $s === TicketStatus::PENDING || $s === TicketStatus::SENT_BACK;
    }
}
