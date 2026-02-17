<?php

declare(strict_types=1);

namespace App\Controller\Blog;

use App\Entity\Blog\Article\Article;
use App\Entity\Blog\Comment\Comment;
use App\Form\Blog\Comment\CommentPublicType;
use App\Repository\Blog\Article\ArticleRepository;
use App\Repository\Blog\Comment\CommentRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

class BlogController extends AbstractController
{
    public function __construct(
        private readonly ArticleRepository $articleRepository,
        private readonly CommentRepository $commentRepository,
        private readonly \App\Repository\Blog\Article\CategoryRepository $categoryRepository,
        private readonly \App\Repository\Blog\Article\TagRepository $tagRepository,
        private readonly \App\Repository\Blog\Article\ArticleReactionRepository $reactionRepository,
        private readonly \Knp\Component\Pager\PaginatorInterface $paginator
    ) {
    }

    #[Route('/blog', name: 'blog_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $search = $request->query->get('q');
        $order = $request->query->get('order', 'DESC');
        if (!in_array($order, ['ASC', 'DESC'], true)) {
            $order = 'DESC';
        }

        $categoryId = $request->query->get('category') ? (int) $request->query->get('category') : null;
        $tagId = $request->query->get('tag') ? (int) $request->query->get('tag') : null;

        $query = $this->articleRepository->getQueryPublishedBySearchAndOrder(
            $search === '' ? null : $search,
            $order,
            $categoryId,
            $tagId
        );

        $pagination = $this->paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            8 // Articles per page
        );

        $selectedCategory = $categoryId ? $this->categoryRepository->find($categoryId) : null;
        $selectedTag = $tagId ? $this->tagRepository->find($tagId) : null;

        return $this->render('blog/index.html.twig', [
            'pagination' => $pagination,
            'search' => $search ?? '',
            'order' => $order,
            'selectedCategory' => $selectedCategory,
            'selectedTag' => $selectedTag,
        ]);
    }

    #[Route('/blog/{slug}', name: 'blog_show', methods: ['GET', 'POST'])]
    public function show(Request $request, string $slug): Response
    {
        $article = $this->articleRepository->findOnePublishedBySlug($slug);
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

            return $this->redirectToRoute('blog_show', ['slug' => $article->getSlug()], Response::HTTP_SEE_OTHER);
        }

        $comments = $this->commentRepository->findByArticleOrderByCreatedAt($article->getId(), 'DESC');

        return $this->render('blog/show.html.twig', [
            'article' => $article,
            'comments' => $comments,
            'form' => $form,
        ]);
    }

    #[Route('/blog/{slug}/react/{type}', name: 'blog_react', requirements: ['type' => 'like|dislike'], methods: ['POST'])]
    public function react(Article $article, string $type): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'You must be logged in to react.'], Response::HTTP_UNAUTHORIZED);
        }

        /** @var \App\Entity\User $user */
        $reaction = $this->reactionRepository->findOneByArticleAndUser($article->getId(), $user->getId());

        if ($reaction) {
            if ($reaction->getType() === $type) {
                // Toggle off
                $this->reactionRepository->remove($reaction, true);
            } else {
                // Switch type
                $reaction->setType($type);
                $this->reactionRepository->save($reaction, true);
            }
        } else {
            // New reaction
            $reaction = new \App\Entity\Blog\Article\ArticleReaction();
            $reaction->setArticle($article);
            $reaction->setUser($user);
            $reaction->setType($type);
            $this->reactionRepository->save($reaction, true);
        }

        return $this->json([
            'likes' => $article->getLikesCount(),
            'dislikes' => $article->getDislikesCount(),
            'userReaction' => ($article->getUserReaction($user))?->getType(),
        ]);
    }
}
