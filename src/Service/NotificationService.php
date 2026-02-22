<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Notification;
use App\Entity\User;
use App\Repository\NotificationRepository;
use Doctrine\ORM\EntityManagerInterface;

class NotificationService
{
    public function __construct(
        private readonly NotificationRepository $notificationRepository,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    public function notify(User $user, string $message, string $type = 'info', ?int $relatedId = null): Notification
    {
        $notification = new Notification();
        $notification->setUser($user);
        $notification->setMessage($message);
        $notification->setType($type);
        $notification->setRelatedId($relatedId);

        $this->notificationRepository->save($notification);

        return $notification;
    }

    public function markAsRead(Notification $notification): void
    {
        $notification->setIsRead(true);
        $this->entityManager->flush();
    }

    public function markAllAsRead(User $user): void
    {
        $unread = $this->notificationRepository->findUnreadByUser($user);
        foreach ($unread as $notif) {
            $notif->setIsRead(true);
        }
        $this->entityManager->flush();
    }
}
