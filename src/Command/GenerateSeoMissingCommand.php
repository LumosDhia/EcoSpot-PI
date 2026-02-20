<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Blog\Article\Article;
use App\Repository\Blog\Article\ArticleRepository;
use App\Service\AiSeoService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:generate-seo-missing',
    description: 'Generates SEO metadata for articles that do not have it.',
)]
class GenerateSeoMissingCommand extends Command
{
    public function __construct(
        private ArticleRepository $articleRepository,
        private AiSeoService $aiSeoService,
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $articles = $this->articleRepository->findAll();

        $io->title('Generating missing SEO metadata');
        
        $count = 0;
        foreach ($articles as $article) {
            if (empty($article->getSeoDescription()) || empty($article->getSeoKeywords())) {
                $io->text('Processing: ' . $article->getTitle());
                
                $seo = $this->aiSeoService->generateSeoElements($article);
                
                if (!empty($seo)) {
                    if (empty($article->getSeoTitle())) $article->setSeoTitle($seo['title'] ?? null);
                    if (empty($article->getSeoDescription())) $article->setSeoDescription($seo['description'] ?? null);
                    if (empty($article->getSeoKeywords())) $article->setSeoKeywords($seo['keywords'] ?? null);
                    
                    $this->entityManager->persist($article);
                    $count++;
                }
            }
        }

        $this->entityManager->flush();

        $io->success(sprintf('Finished! Generated SEO for %d articles.', $count));

        return Command::SUCCESS;
    }
}
