<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Evenement;
use App\Entity\User;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

class EventNotificationService
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly string $adminEmail
    ) {
    }

    public function notifyEventCreated(Evenement $event, User $creator): void
    {
        // Send email to admin
        try {
            $email = (new TemplatedEmail())
                ->from(new Address('ecospot076@gmail.com', 'EcoSpot'))
                ->to($this->adminEmail)
                ->subject(sprintf('New Event Created: %s', $event->getNom()))
                ->htmlTemplate('emails/event_created.html.twig')
                ->context([
                    'event' => $event,
                    'creator' => $creator,
                ]);

            $this->mailer->send($email);
        } catch (\Exception $e) {
            // Silently fail to prevent app crash
        }
    }


    public function notifyEventUpdated(Evenement $event, User $updatedBy): void
    {
        // Send email to admin
        try {
            $email = (new TemplatedEmail())
                ->from(new Address('ecospot076@gmail.com', 'EcoSpot'))
                ->to($this->adminEmail)
                ->subject(sprintf('Event Updated: %s', $event->getNom()))
                ->htmlTemplate('emails/event_updated.html.twig')
                ->context([
                    'event' => $event,
                    'updatedBy' => $updatedBy,
                ]);

            $this->mailer->send($email);
        } catch (\Exception $e) {
            // Silently fail to prevent app crash
        }
    }


    public function notifyEventDeleted(string $eventName, User $deletedBy): void
    {
        // Send email to admin
        try {
            $email = (new TemplatedEmail())
                ->from(new Address('ecospot076@gmail.com', 'EcoSpot'))
                ->to($this->adminEmail)
                ->subject(sprintf('Event Deleted: %s', $eventName))
                ->htmlTemplate('emails/event_deleted.html.twig')
                ->context([
                    'eventName' => $eventName,
                    'deletedBy' => $deletedBy,
                ]);

            $this->mailer->send($email);
        } catch (\Exception $e) {
            // Silently fail to prevent app crash
        }
    }

}

