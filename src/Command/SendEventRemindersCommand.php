<?php

declare(strict_types=1);

namespace App\Command;

use App\Repository\EvenementRepository;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

#[AsCommand(
    name: 'app:send-event-reminders',
    description: 'Sends email reminders to participants 2 days before an event starts.',
)]
class SendEventRemindersCommand extends Command
{
    public function __construct(
        private readonly EvenementRepository $evenementRepository,
        private readonly MailerInterface $mailer
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $targetDate = (new \DateTimeImmutable())->add(new \DateInterval('P2D'));
        
        $io->title('Sending Event Reminders');
        $io->info('Searching for events starting on: ' . $targetDate->format('Y-m-d'));

        $events = $this->evenementRepository->createQueryBuilder('e')
            ->where('e.dateDebut >= :start')
            ->andWhere('e.dateDebut <= :end')
            ->setParameter('start', $targetDate->setTime(0, 0, 0))
            ->setParameter('end', $targetDate->setTime(23, 59, 59))
            ->getQuery()
            ->getResult();

        if (empty($events)) {
            $io->success('No events found for the target date. No reminders sent.');
            return Command::SUCCESS;
        }

        $sentCount = 0;
        foreach ($events as $event) {
            $io->section('Event: ' . $event->getNom());
            $participants = $event->getParticipants();
            
            if ($participants->isEmpty()) {
                $io->text('No participants for this event.');
                continue;
            }

            foreach ($participants as $user) {
                try {
                    $email = (new TemplatedEmail())
                        ->from(new Address('ecospot076@gmail.com', 'EcoSpot Team'))
                        ->to($user->getEmail())

                        ->subject('Reminder: ' . $event->getNom() . ' is in 2 days!')
                        ->htmlTemplate('emails/event_reminder.html.twig')
                        ->context([
                            'user' => $user,
                            'event' => $event,
                        ]);

                    $this->mailer->send($email);
                    $sentCount++;
                    $io->text('Sent reminder to: ' . $user->getEmail());
                } catch (\Exception $e) {
                    $io->error('Failed to send to ' . $user->getEmail() . ': ' . $e->getMessage());
                }
            }
        }

        $io->success(sprintf('Finished. Sent %d reminder(s).', $sentCount));

        return Command::SUCCESS;
    }
}
