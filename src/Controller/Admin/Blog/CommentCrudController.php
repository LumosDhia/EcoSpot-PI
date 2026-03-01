<?php

declare(strict_types=1);

namespace App\Controller\Admin\Blog;

use App\Entity\Blog\Comment\Comment;
use App\Repository\Blog\Comment\CommentRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/** Admin: list comments, view, delete. Commenting is only under articles (any logged-in user). */
#[Route('/admin/blog/comment')]
class CommentCrudController extends AbstractController
{
    public function __construct(
        private readonly CommentRepository $commentRepository
    ) {
    }

    #[Route('', name: 'admin_blog_comment_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('admin/blog/comment/index.html.twig', [
            'comments' => $this->commentRepository->findBy([], ['createdAt' => 'DESC'], 500),
        ]);
    }

    #[Route('/{id}', name: 'admin_blog_comment_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(Comment $comment): Response
    {
        return $this->render('admin/blog/comment/show.html.twig', [
            'comment' => $comment,
        ]);
    }

    #[Route('/{id}', name: 'admin_blog_comment_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(Request $request, Comment $comment): Response
    {
        $token = $request->request->getString('_token');
        if ($this->isCsrfTokenValid('delete' . $comment->getId(), $token)) {
            $this->commentRepository->remove($comment, true);
            $this->addFlash('success', 'Comment deleted.');
        }

        return $this->redirectToRoute('admin_blog_comment_index', [], Response::HTTP_SEE_OTHER);
    }
}
