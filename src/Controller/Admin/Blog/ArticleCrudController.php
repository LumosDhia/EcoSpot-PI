<?php

declare(strict_types=1);

namespace App\Controller\Admin\Blog;

use App\Entity\Blog\Article\Article;
use App\Form\Blog\Article\ArticleType;
use App\Repository\Blog\Article\ArticleRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/blog/article')]
class ArticleCrudController extends AbstractController
{
    public function __construct(
        private readonly ArticleRepository $articleRepository,
        private readonly \Knp\Component\Pager\PaginatorInterface $paginator,
        private readonly string $projectDir
    ) {
    }

    #[Route('', name: 'admin_blog_article_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        /** @var \App\Entity\User $admin */
        $admin = $this->getUser();
        
        $adminQuery = $this->articleRepository->getQueryAdminOwnArticles($admin);
        $adminPagination = $this->paginator->paginate(
            $adminQuery,
            $request->query->getInt('page_admin', 1),
            5,
            ['pageParameterName' => 'page_admin']
        );

        $ngoQuery = $this->articleRepository->getQueryNgoArticlesForAdmin();
        $ngoPagination = $this->paginator->paginate(
            $ngoQuery,
            $request->query->getInt('page_ngo', 1),
            5,
            ['pageParameterName' => 'page_ngo']
        );

        return $this->render('admin/blog/article/index.html.twig', [
            'admin_pagination' => $adminPagination,
            'ngo_pagination' => $ngoPagination,
        ]);
    }

    #[Route('/new', name: 'admin_blog_article_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $article = new Article();
        $form = $this->createForm(ArticleType::class, $article);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var \App\Entity\User $writer */
            $writer = $this->getUser();
            $article->setWriter($writer);
            $this->applyPublishModeFromForm($form, $article);
            $this->handleArticleHeroImage($form, $article);
            $this->articleRepository->save($article, true);

            $this->addFlash('success', 'Article created successfully.');

            return $this->redirectToRoute('admin_blog_article_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/blog/article/new.html.twig', [
            'article' => $article,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'admin_blog_article_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(Article $article): Response
    {
        return $this->render('admin/blog/article/show.html.twig', [
            'article' => $article,
            'is_ngo_article' => $article->isWrittenByNgo(),
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_blog_article_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(Request $request, Article $article): Response
    {
        $this->denyEditOrPublishForNgoArticle($article);
        $form = $this->createForm(ArticleType::class, $article);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->applyPublishModeFromForm($form, $article);
            $this->handleArticleHeroImage($form, $article);
            $this->articleRepository->save($article, true);

            $this->addFlash('success', 'Article updated successfully.');

            return $this->redirectToRoute('admin_blog_article_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/blog/article/edit.html.twig', [
            'article' => $article,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'admin_blog_article_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(Request $request, Article $article): Response
    {
        $token = $request->request->getString('_token');
        if ($this->isCsrfTokenValid('delete' . $article->getId(), $token)) {
            $this->articleRepository->remove($article, true);
            $this->addFlash('success', 'Article deleted.');
        }

        return $this->redirectToRoute('admin_blog_article_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/publish', name: 'admin_blog_article_publish', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function publish(Request $request, Article $article): Response
    {
        $this->denyEditOrPublishForNgoArticle($article);
        $token = $request->request->getString('_token');
        if ($this->isCsrfTokenValid('publish' . $article->getId(), $token)) {
            $article->setPublishedAt(new \DateTimeImmutable());
            $this->articleRepository->save($article, true);
            $this->addFlash('success', 'Article published.');
        }
        return $this->redirectToRoute('admin_blog_article_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/return-revision', name: 'admin_blog_article_return_revision', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function returnForRevision(Request $request, Article $article): Response
    {
        if (!$article->isWrittenByNgo()) {
            throw new NotFoundHttpException('Article not found.');
        }
        if ($request->isMethod('POST')) {
            $note = $request->request->getString('revision_note', '');
            $article->setPublishedAt(null);
            $article->setAdminRevisionNote($note ?: null);
            $this->articleRepository->save($article, true);
            $this->addFlash('success', 'Article returned to the NGO for revision.');
            return $this->redirectToRoute('admin_blog_article_index', [], Response::HTTP_SEE_OTHER);
        }
        return $this->render('admin/blog/article/return_revision.html.twig', [
            'article' => $article,
        ]);
    }

    private function applyPublishModeFromForm(FormInterface $form, Article $article): void
    {
        $mode = $form->get('publishMode')->getData();
        $scheduledAt = $form->get('scheduledAt')->getData();

        if ($mode === 'publish_now') {
            $article->setPublishedAt(new \DateTimeImmutable());
        } elseif ($mode === 'schedule' && $scheduledAt instanceof \DateTimeInterface) {
            $article->setPublishedAt(\DateTimeImmutable::createFromInterface($scheduledAt));
        } else {
            $article->setPublishedAt(null);
        }
    }

    /** Block edit/publish on NGO articles (admin can only delete or return for revision). */
    private function denyEditOrPublishForNgoArticle(Article $article): void
    {
        if ($article->isWrittenByNgo()) {
            throw new NotFoundHttpException('Article not found.');
        }
    }

    private function handleArticleHeroImage(FormInterface $form, Article $article): void
    {
        $file = $form->get('imageFile')->getData();
        if ($file && $file->isValid()) {
            $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if (in_array($file->getMimeType(), $allowed, true)) {
                $dir = $this->projectDir . '/public/uploads/articles';
                if (!is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }
                $safeName = uniqid('hero_', true) . '.' . ($file->guessExtension() ?: 'jpg');
                try {
                    $file->move($dir, $safeName);
                    $article->setImage('/uploads/articles/' . $safeName);
                    return;
                } catch (FileException $e) {
                }
            }
        }
        // When no thumbnail uploaded, keep pasted URL (form already bound it to article.image)
    }
}
