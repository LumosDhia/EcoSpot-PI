<?php

use App\Entity\Article;
use App\Kernel;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

require dirname(__DIR__).'/vendor/autoload.php';

$kernel = new Kernel('dev', true);
$kernel->boot();
$entityManager = $kernel->getContainer()->get('doctrine')->getManager();

echo "Testing Unique Title Constraint...\n";

$title = "Saving Our Oceans"; // Already exists from seeding

$article = new Article();
$article->setTitle($title);
$article->setContent("Duplicate title test content. This should fail.");

try {
    $entityManager->persist($article);
    $entityManager->flush();
    echo "ERROR: Duplicate title was allowed!\n";
} catch (\Doctrine\DBAL\Exception\UniqueConstraintViolationException $e) {
    echo "SUCCESS: Database prevented duplicate title (SQL Index).\n";
} catch (\Exception $e) {
    echo "CAUGHT: " . get_class($e) . " - " . $e->getMessage() . "\n";
}
