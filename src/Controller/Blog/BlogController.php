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
        private readonly \App\Repository\Blog\Article\ArticleReactionRepository $reactionRepository,
        private readonly \Knp\Component\Pager\PaginatorInterface $paginator,
        private readonly \App\Service\TranslationService $translationService,
        private readonly \Doctrine\ORM\EntityManagerInterface $entityManager
    ) {
    }

    #[Route('/blog', name: 'blog_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $search = $request->query->getString('search', '');
        $search = $search === '' ? null : $search;
        $order = strtoupper($request->query->getString('order', 'DESC'));
        if (!in_array($order, ['ASC', 'DESC'], true)) {
            $order = 'DESC';
        }

        $categoryId = $request->query->get('category') ? (int) $request->query->get('category') : null;
        $tagId = $request->query->get('tag') ? (int) $request->query->get('tag') : null;
        $writerId = $request->query->get('writer') ? (int) $request->query->get('writer') : null;

        // Support translated search: if searching in non-English, try translating search term back to English
        $translatedSearch = null;
        $locale = $request->getLocale();
        if ($search && $locale !== 'en') {
            $translatedSearch = $this->translationService->translate($search, 'en');
        }

        $query = $this->articleRepository->getQueryPublishedBySearchAndOrder(
            null, // No search in SQL yet
            $order,
            $categoryId,
            $tagId,
            $writerId
        );

        if ($search) {
            $articles = $query->getResult();
            $locale = $request->getLocale();
            $filteredArticles = array_filter($articles, function($article) use ($search, $locale) {
                // Get the translated title exactly as shown in Twig
                $translatedTitle = $this->translationService->translate($article->getTitle(), $locale);
                return stripos($translatedTitle, $search) !== false;
            });
            
            // Re-paginate the filtered array
            $pagination = $this->paginator->paginate(
                $filteredArticles,
                $request->query->getInt('page', 1),
                8
            );
        } else {
            $pagination = $this->paginator->paginate(
                $query,
                $request->query->getInt('page', 1),
                8, // Articles per page
                ['wrap-queries' => true, 'fetch_join_collection' => false]
            );
        }

        if ($request->isXmlHttpRequest()) {
            return $this->render('blog/_article_grid.html.twig', [
                'pagination' => $pagination,
            ]);
        }

        $selectedCategory = $categoryId ? $this->entityManager->getReference(\App\Entity\Blog\Article\Category::class, $categoryId) : null;
        $selectedTag = $tagId ? $this->entityManager->getReference(\App\Entity\Blog\Article\Tag::class, $tagId) : null;
        $selectedWriter = $writerId ? $this->entityManager->getReference(\App\Entity\User::class, $writerId) : null;

        return $this->render('blog/index.html.twig', [
            'pagination' => $pagination,
            'search' => $search ?? '',
            'order' => strtolower($order),
            'selectedCategory' => $selectedCategory,
            'selectedTag' => $selectedTag,
            'selectedWriter' => $selectedWriter,
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

        /** @var \App\Entity\User|null $user */
        $user = $this->getUser();
        $comment = new Comment($user);
        $comment->setArticle($article);
        if ($user) {
            $comment->setAuthor(trim($user->getFirstname() . ' ' . $user->getLastname()) ?: $user->getUserIdentifier());
        }
        $form = $this->createForm(CommentPublicType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!$this->getUser()) {
                $this->addFlash('error', 'blog.sign_in_flash');
                return $this->redirectToRoute('app_login', ['_target_path' => $request->getRequestUri()]);
            }
            /** @var \App\Entity\User $user */
            $user = $this->getUser();
            $comment->setAuthor(trim($user->getFirstname() . ' ' . $user->getLastname()) ?: $user->getUserIdentifier());
            $this->commentRepository->save($comment, true);
            $this->addFlash('success', 'blog.comment_success_flash');

            return $this->redirectToRoute('blog_show', ['slug' => $article->getSlug()], Response::HTTP_SEE_OTHER);
        }

        $articleId = $article->getId();
        $comments = $articleId !== null ? $this->commentRepository->findByArticleOrderByCreatedAt($articleId, 'DESC') : [];

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
        
        if ($article->getId() === null) {
            return $this->json(['error' => 'Invalid article.'], Response::HTTP_BAD_REQUEST);
        }

        $reaction = $this->reactionRepository->findOneByArticleAndUser($article, $user);

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
