<?php

declare(strict_types=1);

namespace App\Controller\Ngo;

use App\Entity\Ticket;
use App\Entity\NgoAssignmentRequest;
use App\Enum\TicketStatus;
use App\Repository\TicketRepository;
use App\Repository\NgoAssignmentRequestRepository;
use App\Service\AiNgoSuggestionService;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/ngo/tickets')]
#[IsGranted('ROLE_NGO')]
class NgoTicketController extends AbstractController
{
    public function __construct(
        private readonly TicketRepository $ticketRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly NgoAssignmentRequestRepository $requestRepo,
        private readonly AiNgoSuggestionService $aiNgoService
    ) {
    }

    #[Route('/browse', name: 'ngo_tickets_browse', methods: ['GET'])]
    public function browse(): Response
    {
        /** @var \App\Entity\User $ngo */
        $ngo = $this->getUser();
        
        // Find published tickets not assigned yet
        $qb = $this->ticketRepository->createQueryBuilder('t')
            ->where('t.status = :status')
            ->andWhere('t.assignedNgo IS NULL')
            ->setParameter('status', TicketStatus::PUBLISHED)
            ->orderBy('t.createdAt', 'DESC');
            
        $tickets = $qb->getQuery()->getResult();

        $recommendedTickets = [];
        $otherTickets = [];

        $ngoForAi = [
            [
                'id' => $ngo->getId(),
                'name' => trim($ngo->getFirstname() . ' ' . $ngo->getLastname()),
                'description' => $ngo->getNgoDescription() ?? 'No description'
            ]
        ];

        // AI recommendation: checking one by one (simplified for UX)
        // Note: In a production app, we would cache this or batch predict.
        foreach ($tickets as $ticket) {
            $isRecommended = false;
            // First check basic domain matching heuristic, or strictly AI
            $suggestion = $this->aiNgoService->suggestNgo(
                $ticket->getTitle(),
                $ticket->getDescription(),
                $ngoForAi
            );
            
            if ($suggestion && $suggestion['suggested_ngo_id'] === $ngo->getId()) {
                $isRecommended = true;
                $ticket->aiRecommendationReason = $suggestion['reason'];
            }
            
            if ($isRecommended) {
                $recommendedTickets[] = $ticket;
            } else {
                $otherTickets[] = $ticket;
            }
        }

        // Check if the user already requested a ticket
        $myRequests = $this->requestRepo->findBy(['ngo' => $ngo, 'status' => 'PENDING']);
        $requestedTicketIds = array_map(fn($r) => $r->getTicket()->getId(), $myRequests);

        return $this->render('ngo/tickets_browse.html.twig', [
            'recommendedTickets' => $recommendedTickets,
            'otherTickets' => $otherTickets,
            'requestedTicketIds' => $requestedTicketIds,
        ]);
    }

    #[Route('/{id}/request', name: 'ngo_ticket_request', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function requestTicket(Request $request, Ticket $ticket): Response
    {
        if (!$this->isCsrfTokenValid('request_ticket_' . $ticket->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid token.');
            return $this->redirectToRoute('ngo_tickets_browse');
        }

        if ($ticket->getStatus() !== TicketStatus::PUBLISHED || $ticket->getAssignedNgo() !== null) {
            $this->addFlash('warning', 'This ticket is no longer available.');
            return $this->redirectToRoute('ngo_tickets_browse');
        }

        /** @var \App\Entity\User $ngo */
        $ngo = $this->getUser();
        
        $existingReq = $this->requestRepo->findOneBy(['ticket' => $ticket, 'ngo' => $ngo]);
        if ($existingReq) {
            $this->addFlash('info', 'You already requested this ticket.');
            return $this->redirectToRoute('ngo_tickets_browse');
        }

        $assignmentRequest = new NgoAssignmentRequest();
        $assignmentRequest->setTicket($ticket);
        $assignmentRequest->setNgo($ngo);
        $this->entityManager->persist($assignmentRequest);
        $this->entityManager->flush();

        // Notify admins (Assuming admin user ID 1 or a general role config)
        $this->addFlash('success', 'Your request has been sent to the administrators.');
        return $this->redirectToRoute('ngo_tickets_browse');
    }

    #[Route('/my-ticket', name: 'ngo_my_ticket', methods: ['GET'])]
    public function myTicket(): Response
    {
        $ngo = $this->getUser();
        
        // An NGO can only have one active ticket at a time
        $qb = $this->ticketRepository->createQueryBuilder('t')
            ->where('t.assignedNgo = :ngo')
            ->andWhere('t.status IN (:statuses)')
            ->setParameter('ngo', $ngo)
            ->setParameter('statuses', [TicketStatus::ASSIGNED, TicketStatus::IN_PROGRESS])
            ->setMaxResults(1);
            
        $ticket = $qb->getQuery()->getOneOrNullResult();

        return $this->render('ngo/my_ticket.html.twig', [
            'ticket' => $ticket,
        ]);
    }

    #[Route('/{id}/accept', name: 'ngo_ticket_accept', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function acceptTicket(Request $request, Ticket $ticket): Response
    {
        if (!$this->isCsrfTokenValid('accept_ticket_' . $ticket->getId(), (string) $request->request->get('_token'))) {
            return $this->redirectToRoute('ngo_my_ticket');
        }

        if ($ticket->getAssignedNgo() !== $this->getUser() || $ticket->getStatus() !== TicketStatus::ASSIGNED) {
            $this->addFlash('error', 'Invalid action.');
            return $this->redirectToRoute('ngo_my_ticket');
        }

        $ticket->setStatus(TicketStatus::IN_PROGRESS);
        $this->entityManager->flush();
        $this->addFlash('success', 'You have accepted the ticket. It is now in progress.');

        return $this->redirectToRoute('ngo_my_ticket');
    }

    #[Route('/{id}/update', name: 'ngo_ticket_update', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function updateTicket(Request $request, Ticket $ticket): Response
    {
        if (!$this->isCsrfTokenValid('update_ticket_' . $ticket->getId(), (string) $request->request->get('_token'))) {
            return $this->redirectToRoute('ngo_my_ticket');
        }

        if ($ticket->getAssignedNgo() !== $this->getUser() || $ticket->getStatus() !== TicketStatus::IN_PROGRESS) {
            $this->addFlash('error', 'Invalid action.');
            return $this->redirectToRoute('ngo_my_ticket');
        }

        $notes = $request->request->getString('ngo_notes');
        $ticket->setNgoNotes($notes !== '' ? $notes : null);
        $this->entityManager->flush();
        
        $this->addFlash('success', 'Progress notes updated.');
        return $this->redirectToRoute('ngo_my_ticket');
    }

    #[Route('/{id}/complete', name: 'ngo_ticket_complete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function completeTicket(Request $request, Ticket $ticket): Response
    {
        if (!$this->isCsrfTokenValid('complete_ticket_' . $ticket->getId(), (string) $request->request->get('_token'))) {
            return $this->redirectToRoute('ngo_my_ticket');
        }

        if ($ticket->getAssignedNgo() !== $this->getUser() || $ticket->getStatus() !== TicketStatus::IN_PROGRESS) {
            $this->addFlash('error', 'Invalid action.');
            return $this->redirectToRoute('ngo_my_ticket');
        }

        $ticket->setStatus(TicketStatus::COMPLETED);
        $ticket->setCompletedBy($this->getUser());
        
        $now = new \DateTimeImmutable();
        $ticket->setAchievedAt($now);
        $ticket->setCompletionSubmittedAt($now);

        $this->entityManager->flush();
        
        $this->addFlash('success', 'Congratulations! Ticket completed and added to achievements.');
        return $this->redirectToRoute('ngo_achievements');
    }

    #[Route('/achievements', name: 'ngo_achievements', methods: ['GET'])]
    public function achievements(): Response
    {
        $ngo = $this->getUser();
        
        $qb = $this->ticketRepository->createQueryBuilder('t')
            ->where('t.completedBy = :ngo')
            ->andWhere('t.status = :status')
            ->setParameter('ngo', $ngo)
            ->setParameter('status', TicketStatus::COMPLETED)
            ->orderBy('t.achievedAt', 'DESC');
            
        $tickets = $qb->getQuery()->getResult();

        return $this->render('ngo/achievements.html.twig', [
            'tickets' => $tickets,
        ]);
    }
}
