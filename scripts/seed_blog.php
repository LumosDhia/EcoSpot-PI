<?php

declare(strict_types=1);

use App\Entity\Article;
use App\Entity\Category;
use App\Entity\Tag;
use App\Entity\User;
use App\Kernel;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Dotenv\Dotenv;

require __DIR__ . '/../vendor/autoload.php';

(new Dotenv())->bootEnv(__DIR__ . '/../.env');

$kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$kernel->boot();

$container = $kernel->getContainer();
$entityManager = $container->get('doctrine')->getManager();

echo "Seeding Categories...\n";
$categories = [];
$categoryRepo = $entityManager->getRepository(Category::class);
foreach (['Ocean Conservation', 'Reforestation', 'Urban Sustainability'] as $name) {
    $category = $categoryRepo->findOneBy(['name' => $name]);
    if (!$category) {
        $category = new Category();
        $category->setName($name);
        $entityManager->persist($category);
    }
    $categories[$name] = $category;
}

echo "Seeding Tags...\n";
$tags = [];
$tagRepo = $entityManager->getRepository(Tag::class);
foreach (['plasticfree', 'trees', 'green', 'nature'] as $name) {
    $tag = $tagRepo->findOneBy(['name' => $name]);
    if (!$tag) {
        $tag = new Tag();
        $tag->setName($name);
        $entityManager->persist($tag);
    }
    $tags[$name] = $tag;
}

$admin = $entityManager->getRepository(User::class)->find(1);
if (!$admin) {
    echo "Error: Admin user (ID 1) not found.\n";
    exit(1);
}

echo "Seeding Articles...\n";

// Article 1
$article1 = new Article();
$article1->setTitle("Saving Our Oceans");
$article1->setContent("Our oceans are filling with plastic. We need to take action now to protect marine life and ensure a healthy planet for future generations. Reducing single-use plastics is a vital first step.");
$article1->setWriter($admin);
$article1->setCategory($categories['Ocean Conservation']);
$article1->addTag($tags['plasticfree']);
$article1->addTag($tags['nature']);
$article1->setViews(150);
$article1->setPublishedAt(new \DateTimeImmutable());
$entityManager->persist($article1);

// Article 2
$article2 = new Article();
$article2->setTitle("Planting a Billion Trees");
$article2->setContent("Trees are the lungs of our planet. Reforestation projects across the globe are working to soak up carbon and restore biodiversity. Every tree planted makes a difference.");
$article2->setWriter($admin);
$article2->setCategory($categories['Reforestation']);
$article2->addTag($tags['trees']);
$article2->addTag($tags['green']);
$article2->setViews(85);
$article2->setPublishedAt(new \DateTimeImmutable());
$entityManager->persist($article2);

// Article 3
$article3 = new Article();
$article3->setTitle("Smart Cities for a Green Future");
$article3->setContent("Urban environments don't have to be concrete jungles. By implementing smart technologies and green infrastructure, we can create sustainable cities that thrive alongside nature.");
$article3->setWriter($admin);
$article3->setCategory($categories['Urban Sustainability']);
$article3->addTag($tags['green']);
$article3->setViews(210);
$article3->setPublishedAt(new \DateTimeImmutable());
$entityManager->persist($article3);

$entityManager->flush();

echo "Seeding completed successfully!\n";
