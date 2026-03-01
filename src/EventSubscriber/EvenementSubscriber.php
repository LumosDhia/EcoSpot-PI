<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Entity\Evenement;
use App\Service\EventNotificationService;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;
use Symfony\Bundle\SecurityBundle\Security;

#[AsEntityListener(event: Events::postPersist, method: 'postPersist', entity: Evenement::class)]
#[AsEntityListener(event: Events::postUpdate, method: 'postUpdate', entity: Evenement::class)]
#[AsEntityListener(event: Events::postRemove, method: 'postRemove', entity: Evenement::class)]
class EvenementSubscriber
{
    public function __construct(
        private readonly EventNotificationService $notificationService,
        private readonly Security $security
    ) {
    }

    public function postPersist(Evenement $entity): void
    {
        /** @var \App\Entity\User|null $user */
        $user = $this->security->getUser();
        if ($user === null) {
            return;
        }

        // Notify admin about new event
        $this->notificationService->notifyEventCreated($entity, $user);
    }

    public function postUpdate(Evenement $entity): void
    {
        /** @var \App\Entity\User|null $user */
        $user = $this->security->getUser();
        if ($user === null) {
            return;
        }

        // Notify admin about event update
        $this->notificationService->notifyEventUpdated($entity, $user);
    }

    public function postRemove(Evenement $entity): void
    {
        /** @var \App\Entity\User|null $user */
        $user = $this->security->getUser();
        if ($user === null) {
            return;
        }

        // Notify admin about event deletion
        $this->notificationService->notifyEventDeleted($entity->getNom(), $user);
    }
}
