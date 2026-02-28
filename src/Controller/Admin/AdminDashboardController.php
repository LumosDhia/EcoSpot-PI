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
        private readonly TicketRepository $ticketRepository,
        private readonly \App\Repository\Blog\Article\ArticleRepository $articleRepository,
        private readonly \App\Repository\Blog\Comment\CommentRepository $commentRepository,
        private readonly \App\Repository\EvenementRepository $evenementRepository,
        private readonly \App\Repository\UserRepository $userRepository,
        private readonly \App\Repository\SponsorRepository $sponsorRepository
    ) {
    }

    #[Route('', name: 'admin_dashboard', methods: ['GET'])]
    public function index(): Response
    {
        $pendingTicketsCount = \count($this->ticketRepository->findPendingForAdmin());
        $pendingCompletionsCount = \count($this->ticketRepository->findPendingCompletions());
        $articlesCount = \count($this->articleRepository->findAll());
        $commentsCount = \count($this->commentRepository->findAll());
        $eventsCount = \count($this->evenementRepository->findAll());
        $usersCount = \count($this->userRepository->findAll());
        $sponsorsCount = \count($this->sponsorRepository->findAll());

        return $this->render('admin/dashboard.html.twig', [
            'pending_tickets_count' => $pendingTicketsCount,
            'pending_completions_count' => $pendingCompletionsCount,
            'articles_count' => $articlesCount,
            'comments_count' => $commentsCount,
            'events_count' => $eventsCount,
            'users_count' => $usersCount,
            'sponsors_count' => $sponsorsCount,
        ]);
    }
}
