<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Ticket;
use App\Entity\NgoAssignmentRequest;
use App\Enum\TicketStatus;
use App\Form\TicketType;
use App\Repository\TicketRepository;
use App\Repository\UserRepository;
use App\Repository\NgoAssignmentRequestRepository;
use App\Service\AiNgoSuggestionService;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
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
        private readonly UserRepository $userRepository,
        private readonly NgoAssignmentRequestRepository $requestRepo,
        private readonly AiNgoSuggestionService $aiNgoService,
        private readonly NotificationService $notificationService
    ) {
    }

    #[Route('', name: 'admin_ticket_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $query = $request->query->getString('q');
        $query = $query === '' ? null : $query;
        $sortBy = $request->query->getString('sort', 'newest');

        return $this->render('admin/ticket/index.html.twig', [
            'tickets' => $this->ticketRepository->findAllForAdmin($query, $sortBy),
            'currentQuery' => $query,
            'currentSort' => $sortBy,
        ]);
    }

    #[Route('/{id}/show', name: 'admin_ticket_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(Ticket $ticket): Response
    {
        // Fetch NGOs
        $ngos = $this->userRepository->createQueryBuilder('u')
            ->where('u.roles LIKE :role')
            ->setParameter('role', '%"ROLE_NGO"%')
            ->getQuery()
            ->getResult();

        $ngoListForAi = [];
        foreach ($ngos as $ngo) {
            $ngoListForAi[] = [
                'id' => $ngo->getId(),
                'name' => trim($ngo->getFirstname() . ' ' . $ngo->getLastname()) ?: $ngo->getEmail(),
                'description' => $ngo->getNgoDescription() ?? 'No description provided yet.'
            ];
        }

        $suggestedNgoId = null;
        $suggestedReason = null;

        if ($ticket->getStatus() === TicketStatus::PUBLISHED && !$ticket->getAssignedNgo()) {
            $suggestion = $this->aiNgoService->suggestNgo(
                $ticket->getTitle(),
                $ticket->getDescription(),
                $ngoListForAi
            );
            if ($suggestion) {
                $suggestedNgoId = $suggestion['suggested_ngo_id'];
                $suggestedReason = $suggestion['reason'];
            }
        }

        return $this->render('admin/ticket/show.html.twig', [
            'ticket' => $ticket,
            'ngos' => $ngos,
            'suggestedNgoId' => $suggestedNgoId,
            'suggestedReason' => $suggestedReason,
        ]);
    }

    #[Route('/{id}/assign-ngo', name: 'admin_ticket_assign_ngo', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function assignNgo(Request $request, Ticket $ticket): Response
    {
        if (!$this->isCsrfTokenValid('assign_ngo_' . $ticket->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid token.');
            return $this->redirectToRoute('admin_ticket_show', ['id' => $ticket->getId()]);
        }

        $ngoId = $request->request->get('ngo_id');
        $ngo = $this->userRepository->find($ngoId);

        if (!$ngo || !in_array('ROLE_NGO', $ngo->getRoles(), true)) {
            $this->addFlash('error', 'Invalid NGO selected.');
            return $this->redirectToRoute('admin_ticket_show', ['id' => $ticket->getId()]);
        }

        $ticket->setAssignedNgo($ngo);
        $ticket->setStatus(TicketStatus::ASSIGNED);
        $this->entityManager->flush();

        $this->notificationService->notify(
            $ngo,
            sprintf('You have been assigned to ticket: "%s"', $ticket->getTitle()),
            'primary',
            $ticket->getId()
        );

        $this->addFlash('success', 'Ticket assigned successfully.');
        return $this->redirectToRoute('admin_ticket_show', ['id' => $ticket->getId()]);
    }

    #[Route('/assignment-requests', name: 'admin_ngo_assignment_requests', methods: ['GET'])]
    public function assignmentRequests(): Response
    {
        $requests = $this->requestRepo->findBy(['status' => 'PENDING'], ['createdAt' => 'DESC']);

        return $this->render('admin/ticket/assignment_requests.html.twig', [
            'requests' => $requests,
        ]);
    }

    #[Route('/assignment-requests/{id}/{action}', name: 'admin_ngo_assignment_request_handle', requirements: ['id' => '\d+', 'action' => 'approve|reject'], methods: ['POST'])]
    public function handleAssignmentRequest(Request $request, NgoAssignmentRequest $ngoRequest, string $action): Response
    {
        if (!$this->isCsrfTokenValid('handle_request_' . $ngoRequest->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid token.');
            return $this->redirectToRoute('admin_ngo_assignment_requests');
        }

        if ($action === 'approve') {
            $ticket = $ngoRequest->getTicket();
            
            // Check if ticket is still available
            if ($ticket->getAssignedNgo() !== null) {
                $this->addFlash('warning', 'This ticket was already assigned to an NGO.');
                $ngoRequest->setStatus('REJECTED');
            } else {
                $ngoRequest->setStatus('APPROVED');
                $ticket->setAssignedNgo($ngoRequest->getNgo());
                $ticket->setStatus(TicketStatus::ASSIGNED);
                
                // Reject other pending requests for this ticket
                $otherRequests = $this->requestRepo->findBy(['ticket' => $ticket, 'status' => 'PENDING']);
                foreach ($otherRequests as $otherReq) {
                    if ($otherReq->getId() !== $ngoRequest->getId()) {
                        $otherReq->setStatus('REJECTED');
                    }
                }

                $this->notificationService->notify(
                    $ngoRequest->getNgo(),
                    sprintf('Your request to be assigned to ticket "%s" was approved!', $ticket->getTitle()),
                    'success',
                    $ticket->getId()
                );
                
                $this->addFlash('success', 'Request approved. Ticket is now assigned.');
            }
        } elseif ($action === 'reject') {
            $ngoRequest->setStatus('REJECTED');
            
            $this->notificationService->notify(
                $ngoRequest->getNgo(),
                sprintf('Your request to be assigned to ticket "%s" was rejected.', $ngoRequest->getTicket()->getTitle()),
                'danger',
                $ngoRequest->getTicket()->getId()
            );

            $this->addFlash('success', 'Request rejected.');
        }

        $this->entityManager->flush();

        return $this->redirectToRoute('admin_ngo_assignment_requests');
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
