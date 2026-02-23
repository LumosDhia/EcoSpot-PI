<?php

declare(strict_types=1);

namespace App\Controller\Blog\Article;

use App\Entity\Blog\Article\Article;
use App\Service\AiSeoService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/blog/api/seo', name: 'blog_api_seo_')]
class ArticleSeoApiController extends AbstractController
{
    public function __construct(
        private readonly AiSeoService $aiSeoService
    ) {
    }

    #[Route('/generate', name: 'generate', methods: ['POST'])]
    public function generate(Request $request): JsonResponse
    {
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_NGO')) {
            return new JsonResponse(['error' => 'Access denied'], 403);
        }

        $data = json_decode($request->getContent(), true);
        $title = $data['title'] ?? '';
        $content = $data['content'] ?? '';

        if (empty($title) || empty($content)) {
            return new JsonResponse(['error' => 'Title and content are required'], 400);
        }

        // Create a temporary article object for the service
        $article = new Article();
        $article->setTitle($title);
        $article->setContent($content);

        try {
            $seo = $this->aiSeoService->generateSeoElements($article);
            if (empty($seo)) {
                return new JsonResponse(['error' => 'AI failed to generate SEO elements'], 500);
            }
            return new JsonResponse($seo);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 500);
        }
    }
    #[Route('/generate-titles', name: 'generate_titles', methods: ['POST'])]
    public function generateTitles(Request $request): JsonResponse
    {
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_NGO')) {
            return new JsonResponse(['error' => 'Access denied'], 403);
        }

        $data = json_decode($request->getContent(), true);
        $title = $data['title'] ?? '';
        $content = $data['content'] ?? '';

        if (empty($title) || empty($content)) {
            return new JsonResponse(['error' => 'Title and content are required'], 400);
        }

        $article = new Article();
        $article->setTitle($title);
        $article->setContent($content);

        try {
            $titles = $this->aiSeoService->generateTitleIdeas($article);
            return new JsonResponse($titles);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 500);
        }
    }
}
