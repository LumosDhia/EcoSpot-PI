<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[AsCommand(name: 'app:send-mail')]
final class SendMailCommand extends Command
{
    private MailerInterface $mailer;
    private UrlGeneratorInterface $urlGenerator;

    public function __construct(MailerInterface $mailer, UrlGeneratorInterface $urlGenerator)
    {
        $this->mailer = $mailer;
        $this->urlGenerator = $urlGenerator;
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Mocking resetToken data
        $resetToken = new class {
            public $token = 'test-token-123';
            public $expirationMessageKey = 'reset_password_request.expired';
            public $expirationMessageData = ['%count%' => 1, '%unit%' => 'hour'];
        };

        $email = (new TemplatedEmail())
            ->from(new Address('ecospot076@gmail.com', 'EcoSpot Test'))
            ->to(new Address('wiemjouini77@gmail.com'))
            ->subject('Gmail SMTP Test with Templates')
            ->htmlTemplate('reset_password/email.html.twig')
            ->textTemplate('reset_password/email.txt.twig')
            ->context([
                'resetToken' => $resetToken,
            ])
        ;

        $this->mailer->send($email);

        $output->writeln('Email sent successfully with templates!');

        return Command::SUCCESS;
    }
}
