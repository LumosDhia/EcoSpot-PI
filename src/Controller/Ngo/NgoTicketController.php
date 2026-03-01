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
