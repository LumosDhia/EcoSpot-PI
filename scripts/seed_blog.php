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
foreach (['Ocean Conservation', 'Reforestation', 'Urban Sustainability'] as $name) {
    $category = $entityManager->getRepository(Category::class)->findOneBy(['name' => $name]);
    if (!$category) {
        $category = new Category();
        $category->setName($name);
        $entityManager->persist($category);
    }
    $categories[$name] = $category;
}

echo "Seeding Tags...\n";
$tags = [];
foreach (['plasticfree', 'trees', 'green', 'nature'] as $name) {
    $tag = $entityManager->getRepository(Tag::class)->findOneBy(['name' => $name]);
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

$articleData = [
    [
        'title' => "Saving Our Oceans",
        'content' => "Our oceans are filling with plastic. We need to take action now to protect marine life and ensure a healthy planet for future generations. Reducing single-use plastics is a vital first step.",
        'category' => 'Ocean Conservation',
        'tags' => ['plasticfree', 'nature'],
        'views' => 150
    ],
    [
        'title' => "Planting a Billion Trees",
        'content' => "Trees are the lungs of our planet. Reforestation projects across the globe are working to soak up carbon and restore biodiversity. Every tree planted makes a difference.",
        'category' => 'Reforestation',
        'tags' => ['trees', 'green'],
        'views' => 85
    ],
    [
        'title' => "Smart Cities for a Green Future",
        'content' => "Urban environments don't have to be concrete jungles. By implementing smart technologies and green infrastructure, we can create sustainable cities that thrive alongside nature.",
        'category' => 'Urban Sustainability',
        'tags' => ['green'],
        'views' => 210
    ]
];

foreach ($articleData as $data) {
    $article = $entityManager->getRepository(Article::class)->findOneBy(['title' => $data['title']]);
    if (!$article) {
        $article = new Article();
        $article->setTitle($data['title']);
        $article->setContent($data['content']);
        $article->setWriter($admin);
        $article->setCategory($categories[$data['category']]);
        foreach ($data['tags'] as $tagName) {
            $article->addTag($tags[$tagName]);
        }
        $article->setViews($data['views']);
        $article->setPublishedAt(new \DateTimeImmutable());
        $entityManager->persist($article);
    }
}

$entityManager->flush();

echo "Seeding completed successfully!\n";
