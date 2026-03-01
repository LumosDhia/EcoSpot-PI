<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Ticket;
use App\Form\TicketCompletionType;
use App\Repository\TicketRepository;
use App\Service\OpenMeteoWeatherService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class PublicTicketsController extends AbstractController
{
    public function __construct(
        private readonly TicketRepository $ticketRepository,
        private readonly OpenMeteoWeatherService $weatherService
    ) {
    }

    /** Published tickets visible to everyone. */
    #[Route('/tickets', name: 'public_tickets', methods: ['GET'])]
    public function index(): Response
    {
        $tickets = $this->ticketRepository->findPublished();

        return $this->render('ticket/public_list.html.twig', [
            'tickets' => $tickets,
        ]);
    }

    /** Single published ticket with weather when lat/long present. */
    #[Route('/tickets/{id}', name: 'public_ticket_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(Ticket $ticket): Response
    {
        if ($ticket->getStatus() !== \App\Enum\TicketStatus::PUBLISHED) {
            throw $this->createNotFoundException('Ticket not found.');
        }

        $forecast = [];
        if ($ticket->getLatitude() !== null && $ticket->getLongitude() !== null) {
            $forecast = $this->weatherService->getWeeklyForecast($ticket->getLatitude(), $ticket->getLongitude());
        }

        return $this->render('ticket/show.html.twig', [
            'ticket' => $ticket,
            'public_view' => true,
            'forecast' => $forecast,
        ]);
    }

    /** Submit completion (I completed this ticket) – user or NGO only. */
    #[Route('/tickets/{id}/complete', name: 'public_ticket_complete', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function complete(Request $request, Ticket $ticket): Response
    {
        if ($ticket->getStatus() !== \App\Enum\TicketStatus::PUBLISHED) {
            throw $this->createNotFoundException('Ticket not found.');
        }
        if ($ticket->hasCompletionSubmitted()) {
            $this->addFlash('info', 'A completion has already been submitted for this ticket.');
            return $this->redirectToRoute('public_ticket_show', ['id' => $ticket->getId()]);
        }

        $form = $this->createForm(TicketCompletionType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('image')->getData();
            $filename = null;
            if ($imageFile) {
                /** @var string $projectDir */
                $projectDir = $this->getParameter('kernel.project_dir');
                $dir = $projectDir . '/public/images/ticket_completions';
                if (!is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }
                $filename = sprintf('%s_%s.%s', $ticket->getId(), uniqid(), $imageFile->guessExtension() ?? 'jpg');
                $imageFile->move($dir, $filename);
            }

            /** @var \App\Entity\User $user */
            $user = $this->getUser();
            $ticket->submitCompletion($user, $form->get('message')->getData(), $filename);
            $this->ticketRepository->save($ticket);

            $this->addFlash('success', 'Your completion has been submitted. An administrator will review it.');
            return $this->redirectToRoute('public_ticket_show', ['id' => $ticket->getId()]);
        }

        return $this->render('ticket/complete.html.twig', [
            'ticket' => $ticket,
            'form' => $form,
        ]);
    }

    /** Achievements page – tickets marked as achieved with who completed them. */
    #[Route('/achievements', name: 'public_achievements', methods: ['GET'])]
    public function achievements(): Response
    {
        $tickets = $this->ticketRepository->findAchieved();

        return $this->render('ticket/achievements.html.twig', [
            'tickets' => $tickets,
        ]);
    }
}
