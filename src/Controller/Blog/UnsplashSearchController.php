<?php

declare(strict_types=1);

namespace App\Controller\Blog;

use App\Service\Blog\UnsplashImageService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/blog/api/unsplash')]
#[IsGranted(new Expression("is_granted('ROLE_ADMIN') or is_granted('ROLE_NGO')"))]
class UnsplashSearchController extends AbstractController
{
    public function __construct(
        private UnsplashImageService $unsplashImageService
    ) {
    }

    #[Route('/search', name: 'blog_api_unsplash_search', methods: ['GET'])]
    public function search(Request $request): JsonResponse
    {
        $query = $request->query->get('q', '');
        $page = (int) $request->query->get('page', 1);

        if (empty($query)) {
            return new JsonResponse(['results' => [], 'total' => 0, 'total_pages' => 0]);
        }

        $results = $this->unsplashImageService->searchImages($query, $page);

        return new JsonResponse($results);
    }
}
