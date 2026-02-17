<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Article;
use App\Entity\Comment;
use App\Form\CommentPublicType;
use App\Repository\ArticleRepository;
use App\Repository\CommentRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

class BlogController extends AbstractController
{
    public function __construct(
        private readonly ArticleRepository $articleRepository,
        private readonly CommentRepository $commentRepository
    ) {
    }

    #[Route('/blog', name: 'blog_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $search = $request->query->get('q');
        $sort = $request->query->get('sort', 'DESC');
        $categoryId = $request->query->get('category');
        $tagId = $request->query->get('tag');

        if (!in_array($sort, ['ASC', 'DESC'], true)) {
            $sort = 'DESC';
        }

        $articles = $this->articleRepository->findPublishedBySearchAndOrder(
            $search === '' ? null : $search,
            $sort,
            $categoryId ? (int) $categoryId : null,
            $tagId ? (int) $tagId : null
        );

        return $this->render('blog/index.html.twig', [
            'articles' => $articles,
            'search' => $search ?? '',
            'sort' => $sort,
            'currentCategoryId' => $categoryId,
            'currentTagId' => $tagId,
        ]);
    }

    #[Route('/blog/{id}', name: 'blog_show', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function show(Request $request, int $id): Response
    {
        $article = $this->articleRepository->findOnePublishedById($id);
        if ($article === null) {
            throw new NotFoundHttpException('Article not found.');
        }

        $article->incrementViews();
        $this->articleRepository->save($article, true);

        $comment = new Comment();
        $comment->setArticle($article);
        if ($this->getUser()) {
            $comment->setAuthor(trim($this->getUser()->getFirstname() . ' ' . $this->getUser()->getLastname()) ?: $this->getUser()->getUserIdentifier());
            $comment->setAuthorUser($this->getUser());
        }
        $form = $this->createForm(CommentPublicType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!$this->getUser()) {
                $this->addFlash('error', 'You must be signed in to leave a comment.');
                return $this->redirectToRoute('app_login', ['_target_path' => $request->getRequestUri()]);
            }
            $comment->setAuthor(trim($this->getUser()->getFirstname() . ' ' . $this->getUser()->getLastname()) ?: $this->getUser()->getUserIdentifier());
            $comment->setAuthorUser($this->getUser());
            $this->commentRepository->save($comment, true);
            $this->addFlash('success', 'Your comment has been published.');

            return $this->redirectToRoute('blog_show', ['id' => $article->getId()], Response::HTTP_SEE_OTHER);
        }

        $comments = $this->commentRepository->findByArticleOrderByCreatedAt($article->getId(), 'DESC');

        return $this->render('blog/show.html.twig', [
            'article' => $article,
            'comments' => $comments,
            'form' => $form,
        ]);
    }
}
