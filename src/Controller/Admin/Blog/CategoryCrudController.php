<?php

declare(strict_types=1);

namespace App\Controller\Admin\Blog;

use App\Entity\Blog\Article\Category;
use App\Form\Blog\Article\CategoryType;
use App\Repository\Blog\Article\CategoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Knp\Component\Pager\PaginatorInterface;

#[Route('/admin/blog/category')]
class CategoryCrudController extends AbstractController
{
    public function __construct(
        private readonly CategoryRepository $categoryRepository,
        private readonly PaginatorInterface $paginator
    ) {
    }

    #[Route('', name: 'admin_blog_category_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $query = $this->categoryRepository->createQueryBuilder('c')
            ->orderBy('c.id', 'DESC')
            ->getQuery();

        $pagination = $this->paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            15 // Categories per page
        );

        return $this->render('admin/blog/category/index.html.twig', [
            'categories' => $pagination,
        ]);
    }

    #[Route('/new', name: 'admin_blog_category_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $category = new Category();
        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->categoryRepository->save($category, true);
            $this->addFlash('success', 'Category created successfully.');
            return $this->redirectToRoute('admin_blog_category_index');
        }

        return $this->render('admin/blog/category/new.html.twig', [
            'category' => $category,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_blog_category_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Category $category): Response
    {
        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->categoryRepository->save($category, true);
            $this->addFlash('success', 'Category updated successfully.');
            return $this->redirectToRoute('admin_blog_category_index');
        }

        return $this->render('admin/blog/category/edit.html.twig', [
            'category' => $category,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'admin_blog_category_delete', methods: ['POST'])]
    public function delete(Request $request, Category $category): Response
    {
        if ($this->isCsrfTokenValid('delete'.$category->getId(), $request->request->getString('_token'))) {
            $this->categoryRepository->remove($category, true);
            $this->addFlash('success', 'Category deleted.');
        }

        return $this->redirectToRoute('admin_blog_category_index');
    }
}
