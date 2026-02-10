<?php

declare(strict_types=1);

namespace App\Controller\Ngo;

use App\Entity\Article;
use App\Entity\Comment;
use App\Entity\Evenement;
use App\Form\ArticleType;
use App\Form\EvenementType;
use App\Repository\ArticleRepository;
use App\Repository\CommentRepository;
use App\Repository\EvenementRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/ngo')]
class NgoDashboardController extends AbstractController
{
    public function __construct(
        private readonly ArticleRepository $articleRepository,
        private readonly CommentRepository $commentRepository,
        private readonly EvenementRepository $evenementRepository,
        private readonly string $projectDir
    ) {
    }

    #[Route('', name: 'ngo_dashboard', methods: ['GET'])]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_NGO');

        return $this->render('ngo/dashboard.html.twig');
    }

    #[Route('/articles', name: 'ngo_articles_index', methods: ['GET'])]
    public function articlesIndex(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_NGO');

        return $this->render('ngo/articles_index.html.twig', [
            'articles' => $this->articleRepository->findAllForAdmin(),
        ]);
    }

    #[Route('/articles/new', name: 'ngo_article_new', methods: ['GET', 'POST'])]
    public function articleNew(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_NGO');

        $article = new Article();
        $form = $this->createForm(ArticleType::class, $article);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $article->setWriter($this->getUser());
            $this->applyPublishModeFromForm($form, $article);
            $this->handleArticleHeroImage($form, $article);
            $this->articleRepository->save($article, true);
            $this->addFlash('success', 'Article created successfully.');
            return $this->redirectToRoute('ngo_articles_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('ngo/article_new.html.twig', [
            'article' => $article,
            'form' => $form,
        ]);
    }

    #[Route('/articles/{id}/edit', name: 'ngo_article_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function articleEdit(Request $request, Article $article): Response
    {
        $this->denyAccessUnlessGranted('ROLE_NGO');

        $form = $this->createForm(ArticleType::class, $article);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->applyPublishModeFromForm($form, $article);
            $this->handleArticleHeroImage($form, $article);
            $this->articleRepository->save($article, true);
            $this->addFlash('success', 'Article updated successfully.');
            return $this->redirectToRoute('ngo_articles_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('ngo/article_edit.html.twig', [
            'article' => $article,
            'form' => $form,
        ]);
    }

    #[Route('/articles/{id}/publish', name: 'ngo_article_publish', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function articlePublish(Request $request, Article $article): Response
    {
        $this->denyAccessUnlessGranted('ROLE_NGO');

        $token = $request->request->getString('_token');
        if ($this->isCsrfTokenValid('publish' . $article->getId(), $token)) {
            $article->setPublishedAt(new \DateTimeImmutable());
            $this->articleRepository->save($article, true);
            $this->addFlash('success', 'Article published.');
        }
        return $this->redirectToRoute('ngo_articles_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/articles/{id}', name: 'ngo_article_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function articleDelete(Request $request, Article $article): Response
    {
        $this->denyAccessUnlessGranted('ROLE_NGO');

        $token = $request->request->getString('_token');
        if ($this->isCsrfTokenValid('delete' . $article->getId(), $token)) {
            $this->articleRepository->remove($article, true);
            $this->addFlash('success', 'Article deleted.');
        }
        return $this->redirectToRoute('ngo_articles_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/comments', name: 'ngo_comments_index', methods: ['GET'])]
    public function commentsIndex(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_NGO');

        return $this->render('ngo/comments_index.html.twig', [
            'comments' => $this->commentRepository->findBy([], ['createdAt' => 'DESC']),
        ]);
    }

    #[Route('/comments/{id}/flag', name: 'ngo_comment_flag', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function commentFlag(Request $request, Comment $comment): Response
    {
        $this->denyAccessUnlessGranted('ROLE_NGO');

        $token = $request->request->getString('_token');
        if ($this->isCsrfTokenValid('flag' . $comment->getId(), $token)) {
            $comment->setFlagged(true);
            $this->commentRepository->save($comment, true);
            $this->addFlash('success', 'Comment flagged for administrator.');
        }

        return $this->redirectToRoute('ngo_comments_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/events', name: 'ngo_events_index', methods: ['GET'])]
    public function eventsIndex(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_NGO');

        return $this->render('ngo/events_index.html.twig', [
            'events' => $this->evenementRepository->findAllOrderedByDate(),
        ]);
    }

    #[Route('/events/new', name: 'ngo_event_new', methods: ['GET', 'POST'])]
    public function eventNew(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_NGO');

        $evenement = new Evenement();
        $form = $this->createForm(EvenementType::class, $evenement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->handleEventImageUpload($form, $evenement);
            $this->evenementRepository->save($evenement, true);
            $this->addFlash('success', 'Event created successfully.');
            return $this->redirectToRoute('ngo_events_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('ngo/event_new.html.twig', [
            'event' => $evenement,
            'form' => $form,
        ]);
    }

    private function handleEventImageUpload($form, Evenement $evenement): void
    {
        $file = $form->get('imageFile')->getData();
        if (!$file) {
            return;
        }
        $uploadDir = $this->projectDir . '/public/images/events';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        $safeName = uniqid('', true) . '.' . $file->guessExtension();
        $file->move($uploadDir, $safeName);
        $evenement->setImage('/images/events/' . $safeName);
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
        // When no thumbnail uploaded, pasted URL stays (form binding)
    }
}
