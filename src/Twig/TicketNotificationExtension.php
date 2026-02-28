<?php

declare(strict_types=1);

namespace App\Twig;

use App\Entity\User;
use App\Enum\TicketStatus;
use App\Repository\TicketRepository;
use App\Repository\NotificationRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class TicketNotificationExtension extends AbstractExtension
{
    public function __construct(
        private readonly NotificationRepository $notificationRepository,
        private readonly Security $security
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('get_user_ticket_notifications', [$this, 'getNotificationCounts']),
            new TwigFunction('get_unread_notifications', [$this, 'getUnreadNotifications']),
        ];
    }

    /**
     * @return array{
     *     total_unread: int,
     *     is_timed_out: bool
     * }
     */
    public function getNotificationCounts(): array
    {
        $user = $this->security->getUser();

        if (!$user instanceof User) {
            return [
                'total_unread' => 0,
                'is_timed_out' => false,
            ];
        }

        $unreadCount = $this->notificationRepository->countUnreadByUser($user);
            
        $isTimedOut = $user->isTimedOut();

        return [
            'total_unread' => $unreadCount,
            'is_timed_out' => $isTimedOut,
        ];
    }

    /**
     * @return \App\Entity\Notification[]
     */
    public function getUnreadNotifications(): array
    {
        $user = $this->security->getUser();

        if (!$user instanceof User) {
            return [];
        }

        return $this->notificationRepository->findUnreadByUser($user);
    }
}
