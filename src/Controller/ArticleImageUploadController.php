<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Image upload for rich-text article content (CKEditor). Allowed for admin and NGO.
 */
#[Route('/blog/editor-upload', name: 'blog_editor_upload_', methods: ['POST'])]
class ArticleImageUploadController extends AbstractController
{
    public function __construct(
        private readonly string $projectDir
    ) {
    }

    #[Route('', name: 'image', methods: ['POST'])]
    public function image(Request $request): JsonResponse
    {
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_NGO')) {
            $this->denyAccessUnlessGranted('ROLE_USER');
        }
        $file = $request->files->get('upload') ?? $request->files->get('file');
        if (!$file || !$file->isValid()) {
            return new JsonResponse(['error' => ['message' => 'No valid file uploaded.']], Response::HTTP_BAD_REQUEST);
        }

        $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($file->getMimeType(), $allowed, true)) {
            return new JsonResponse(['error' => ['message' => 'Invalid file type.']], Response::HTTP_BAD_REQUEST);
        }

        $dir = $this->projectDir . '/public/uploads/article-images';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $safeName = uniqid('article_', true) . '.' . ($file->guessExtension() ?: 'jpg');
        try {
            $file->move($dir, $safeName);
        } catch (FileException $e) {
            return new JsonResponse(['error' => ['message' => 'Upload failed.']], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $url = $request->getBasePath() . '/uploads/article-images/' . $safeName;
        return new JsonResponse(['url' => $url, 'location' => $url]);
    }
}
