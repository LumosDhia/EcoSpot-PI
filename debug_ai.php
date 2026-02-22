<?php

use App\Kernel;
use App\Service\AiTicketTaskService;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Dotenv\Dotenv;

require __DIR__.'/vendor/autoload.php';

(new Dotenv())->bootEnv(__DIR__.'/.env');

$kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$kernel->boot();

$container = $kernel->getContainer();
$aiService = $container->get(AiTicketTaskService::class);

$title = "Garbage in the park";
$description = "There is a lot of plastic waste in the central park near the lake. We need to clean it up.";

echo "Testing AI Task Generation with openrouter/auto...\n";
$tasks = $aiService->generateTasks($title, $description);

echo "Result:\n";
print_r($tasks);

if (empty($tasks)) {
    echo "NO TASKS GENERATED!\n";
} else {
    echo "SUCCESS: " . count($tasks) . " tasks generated.\n";
}
