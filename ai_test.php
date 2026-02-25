<?php
require_once 'vendor/autoload.php';

use App\Kernel;
use App\Service\AiTicketTaskService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

// Initialize Symfony Kernel
$kernel = new Kernel('dev', true);
$kernel->boot();
$container = $kernel->getContainer();

/** @var AiTicketTaskService $aiService */
$aiService = $container->get(AiTicketTaskService::class);

$title = "Leaking water pipe in the garden";
$description = "There is a significant water leak from a main pipe in the community garden near the oak tree. Hundreds of liters are being wasted.";

echo "Testing AI Task Generation...\n";
$result = $aiService->generateTasks($title, $description);

echo "Result:\n";
print_r($result);

if (!empty($result['tasks'])) {
    echo "\nSUCCESS: AI generated " . count($result['tasks']) . " tasks.\n";
} else {
    echo "\nFAILURE: AI could not generate tasks. Check logs or API key.\n";
}
