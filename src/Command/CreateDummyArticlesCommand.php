<?php

namespace App\Command;

use App\Entity\Blog\Article\Article;
use App\Entity\Blog\Article\Category;
use App\Entity\User;
use App\Repository\Blog\Article\ArticleRepository;
use App\Repository\Blog\Article\CategoryRepository;
use App\Repository\UserRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:create-dummy-articles',
    description: 'Creates dummy articles for Admin and NGO users.',
)]
class CreateDummyArticlesCommand extends Command
{
    public function __construct(
        private ArticleRepository $articleRepository,
        private CategoryRepository $categoryRepository,
        private UserRepository $userRepository,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $admin = $this->userRepository->createQueryBuilder('u')
            ->where('u.emailAddress.email = :email')
            ->setParameter('email', 'admin@ecospot.com')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if (!$admin) {
            $admin = $this->userRepository->findOneBy([]); // Get any user if not found
        }

        $ngo = $this->userRepository->createQueryBuilder('u')
            ->where('u.roles LIKE :role')
            ->setParameter('role', '%"ROLE_NGO"%')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if (!$ngo) {
            $io->warning('No NGO user found. Using admin for all articles.');
            $ngo = $admin;
        }

        $categories = $this->categoryRepository->findAll();
        if (empty($categories)) {
            $cat = new Category();
            $cat->setName('Environment');
            $this->categoryRepository->save($cat, true);
            $categories = [$cat];
        }

        $titles = [
            'How to Reduce Your Carbon Footprint',
            'The Importance of Local Waste Management',
            'Saving Water: 10 Tips for Your Home',
            'Protecting Urban Wildlife',
            'Sustainable Gardening for Beginners',
            'The Impact of Plastic on Our Oceans',
            'Renewable Energy: The Future of Our City',
            'Community Clean-up Success Stories',
        ];

        foreach ($titles as $index => $title) {
            $article = new Article();
            $article->setTitle($title);
            $article->setContent('This is a dummy article content for "' . $title . '". It contains more than twenty characters to satisfy validation rules and provides useful environmental information for our community to engage with EcoSpot.');
            $article->setCategory($categories[array_rand($categories)]);
            $article->setWriter($index % 2 === 0 ? $admin : $ngo);
            
            // Randomly publish some
            if (rand(0, 1)) {
                $article->setPublishedAt(new \DateTimeImmutable());
            }

            $this->articleRepository->save($article);
        }

        // Flush all articles
        if (isset($article)) {
            $this->articleRepository->save($article, true);
        }

        $io->success('Dummy articles created successfully!');

        return Command::SUCCESS;
    }
}
