<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Notification;
use App\Service\NotificationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/notifications')]
#[IsGranted('ROLE_USER')]
class NotificationController extends AbstractController
{
    public function __construct(
        private readonly NotificationService $notificationService
    ) {
    }

    #[Route('/{id}/read', name: 'notification_mark_as_read', methods: ['POST'])]
    public function markAsRead(Notification $notification): Response
    {
        if ($notification->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $this->notificationService->markAsRead($notification);

        return $this->redirectToRoute('dashboard');
    }

    #[Route('/clear-all', name: 'notification_clear_all', methods: ['POST'])]
    public function clearAll(): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $this->notificationService->markAllAsRead($user);

        return $this->redirectToRoute('dashboard');
    }
}
