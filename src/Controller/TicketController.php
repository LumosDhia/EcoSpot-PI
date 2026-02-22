<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Ticket;
use App\Enum\TicketStatus;
use App\Form\TicketType;
use App\Repository\TicketRepository;
use App\Service\OpenMeteoWeatherService;
use App\Service\SpamDetectionService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/dashboard/tickets')]
#[IsGranted('ROLE_USER')]
class TicketController extends AbstractController
{
    public function __construct(
        private readonly TicketRepository $ticketRepository,
        private readonly OpenMeteoWeatherService $weatherService,
        private readonly SpamDetectionService $spamDetectionService,
        private readonly \App\Service\NotificationService $notificationService
    ) {
    }

    #[Route('', name: 'ticket_my_list', methods: ['GET'])]
    public function myTickets(Request $request): Response
    {
        $statusFilter = $request->query->get('status');
        $status = $statusFilter && \in_array($statusFilter, array_map(fn (TicketStatus $s) => $s->value, TicketStatus::cases()), true)
            ? TicketStatus::from($statusFilter) : null;

        $tickets = $this->ticketRepository->findByUser($this->getUser(), $status);

        return $this->render('ticket/my_tickets.html.twig', [
            'tickets' => $tickets,
            'currentStatus' => $status,
            'statuses' => TicketStatus::cases(),
        ]);
    }

    #[Route('/new', name: 'ticket_create', methods: ['GET', 'POST'])]
    public function create(Request $request): Response
    {
        $ticket = new Ticket();
        $ticket->setStatus(TicketStatus::PENDING);

        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        if ($user->isTimedOut()) {
            $this->addFlash('error', sprintf('Your account is temporarily in timeout due to multiple spam flags. You can submit new tickets after %s.', $user->getTimeoutUntil()->format('d/m/Y H:i')));
            return $this->redirectToRoute('ticket_my_list');
        }

        $form = $this->createForm(TicketType::class, $ticket);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($ticket->getLatitude() === null || $ticket->getLongitude() === null) {
                $form->addError(new \Symfony\Component\Form\FormError('Please search and select a location from the list. Only chosen places can be used so we can show weather for the ticket.'));
            } else {
                $this->handleTicketImage($form, $ticket);
                $ticket->setUser($this->getUser());
                
                // AI Spam Check
                $isSpam = $this->spamDetectionService->isSpam($ticket->getTitle(), $ticket->getDescription() ?? '');
                $ticket->setIsSpam($isSpam);

                $this->ticketRepository->save($ticket);

                $this->notificationService->notify(
                    $user,
                    sprintf('Your ticket "%s" has been submitted and is pending review.', $ticket->getTitle()),
                    'info',
                    $ticket->getId()
                );

                // Automatic Timeout Logic
                if ($isSpam) {
                    $since = new \DateTimeImmutable('-24 hours');
                    $spamCount = $this->ticketRepository->countRecentSpamByUser($user, $since);
                    
                    if ($spamCount > 3) {
                        $user->setTimeoutUntil(new \DateTimeImmutable('+24 hours'));
                        $this->ticketRepository->save($ticket);
                        
                        $this->notificationService->notify(
                            $user,
                            'Your account has been put in a 24-hour timeout due to repeated spam detection.',
                            'danger'
                        );

                        $this->addFlash('warning', 'Your account has been put in a 24-hour timeout due to repeated spam detection.');
                    }
                }

                $this->addFlash('success', 'Ticket created. It will be reviewed by the administration.');
                return $this->redirectToRoute('ticket_my_list');
            }
        }

        return $this->render('ticket/create.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'ticket_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(Request $request, Ticket $ticket): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        if ($user->isTimedOut()) {
            $this->addFlash('error', sprintf('Your account is temporarily in timeout. You cannot edit tickets until %s.', $user->getTimeoutUntil()->format('d/m/Y H:i')));
            return $this->redirectToRoute('ticket_my_list');
        }

        if ($ticket->getUser() !== $user) {
            $this->addFlash('error', 'You cannot edit this ticket.');
            return $this->redirectToRoute('ticket_my_list');
        }

        if (!in_array($ticket->getStatus(), [TicketStatus::PENDING, TicketStatus::SENT_BACK], true)) {
            $this->addFlash('error', 'This ticket can only be edited when pending or sent back for modification.');
            return $this->redirectToRoute('ticket_my_list');
        }

        $form = $this->createForm(TicketType::class, $ticket);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($ticket->getLatitude() === null || $ticket->getLongitude() === null) {
                $form->addError(new \Symfony\Component\Form\FormError('Please search and select a location from the list. Only chosen places can be used so we can show weather for the ticket.'));
            } else {
                $this->handleTicketImage($form, $ticket);
                $ticket->setStatus(TicketStatus::PENDING);
                $ticket->setAdminNotes(null);
                
                // AI Spam Re-check on resubmit
                $isSpam = $this->spamDetectionService->isSpam($ticket->getTitle(), $ticket->getDescription() ?? '');
                $ticket->setIsSpam($isSpam);

                $this->ticketRepository->save($ticket);

                $this->notificationService->notify(
                    $user,
                    sprintf('You have resubmitted your ticket "%s" for review.', $ticket->getTitle()),
                    'info',
                    $ticket->getId()
                );

                // Automatic Timeout Logic on Edit
                if ($isSpam) {
                    $since = new \DateTimeImmutable('-24 hours');
                    $spamCount = $this->ticketRepository->countRecentSpamByUser($user, $since);
                    
                    if ($spamCount > 3) {
                        $user->setTimeoutUntil(new \DateTimeImmutable('+24 hours'));
                        $this->ticketRepository->save($ticket);

                        $this->notificationService->notify(
                            $user,
                            'Your account has been put in a 24-hour timeout due to repeated spam detection.',
                            'danger'
                        );

                        $this->addFlash('warning', 'Your account has been put in a 24-hour timeout due to repeated spam detection.');
                    }
                }

                $this->addFlash('success', 'Ticket updated and resubmitted for review.');
                return $this->redirectToRoute('ticket_my_list');
            }
        }

        return $this->render('ticket/edit.html.twig', [
            'ticket' => $ticket,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'ticket_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(Ticket $ticket): Response
    {
        if ($ticket->getUser() !== $this->getUser()) {
            $this->addFlash('error', 'You cannot view this ticket.');
            return $this->redirectToRoute('ticket_my_list');
        }

        $forecast = [];
        if ($ticket->getLatitude() !== null && $ticket->getLongitude() !== null) {
            $forecast = $this->weatherService->getWeeklyForecast($ticket->getLatitude(), $ticket->getLongitude());
        }

        return $this->render('ticket/show.html.twig', [
            'ticket' => $ticket,
            'forecast' => $forecast,
        ]);
    }

    private function handleTicketImage(\Symfony\Component\Form\FormInterface $form, Ticket $ticket): void
    {
        $file = $form->get('imageFile')->getData();
        if (!$file || !$file->isValid()) {
            return;
        }
        $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($file->getMimeType(), $allowed, true)) {
            return;
        }
        $dir = $this->getParameter('kernel.project_dir') . '/public/uploads/tickets';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $safeName = uniqid('ticket_', true) . '.' . ($file->guessExtension() ?: 'jpg');
        try {
            $file->move($dir, $safeName);
            $ticket->setImage('/uploads/tickets/' . $safeName);
        } catch (\Symfony\Component\HttpFoundation\File\Exception\FileException $e) {
            // leave ticket.image unchanged
        }
    }

    #[Route('/{id}/delete', name: 'ticket_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(Request $request, Ticket $ticket): Response
    {
        if ($ticket->getUser() !== $this->getUser()) {
            $this->addFlash('error', 'You cannot delete this ticket.');
            return $this->redirectToRoute('ticket_my_list');
        }

        if (!in_array($ticket->getStatus(), [TicketStatus::PENDING, TicketStatus::SENT_BACK], true)) {
            $this->addFlash('error', 'You can only delete tickets that are pending or sent back.');
            return $this->redirectToRoute('ticket_my_list');
        }

        if ($this->isCsrfTokenValid('delete_ticket_' . $ticket->getId(), $request->request->get('_token'))) {
            $this->ticketRepository->remove($ticket);
            $this->addFlash('success', 'Ticket successfully deleted.');
        }

        return $this->redirectToRoute('ticket_my_list');
    }
}
