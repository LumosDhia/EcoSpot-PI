<?php

namespace App\Command;

use App\Service\AiTicketTaskService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:test-ai-tasks',
    description: 'Test AI task generation service',
)]
class TestAiTasksCommand extends Command
{
    private AiTicketTaskService $aiService;

    public function __construct(AiTicketTaskService $aiService)
    {
        $this->aiService = $aiService;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('title', InputArgument::OPTIONAL, 'Ticket title', 'Garbage in the park')
            ->addArgument('description', InputArgument::OPTIONAL, 'Ticket description', 'There is a lot of plastic waste in the central park near the lake. We need to clean it up.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $title = $input->getArgument('title');
        $description = $input->getArgument('description');

        $io->title('Testing AI Task Generation with openrouter/auto');
        $io->info('Title: ' . $title);
        $io->info('Description: ' . $description);

        $tasks = $this->aiService->generateTasks($title, $description);

        if (empty($tasks)) {
            $io->error('No tasks generated. Check the logs.');
        } else {
            $io->success(sprintf('%d tasks generated:', count($tasks)));
            $io->listing($tasks);
        }

        return Command::SUCCESS;
    }
}
