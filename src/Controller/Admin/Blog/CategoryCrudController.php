<?php

declare(strict_types=1);

namespace App\Controller\Admin\Blog;

use App\Entity\Category;
use App\Form\CategoryType;
use App\Repository\CategoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/blog/category')]
class CategoryCrudController extends AbstractController
{
    public function __construct(
        private readonly CategoryRepository $categoryRepository
    ) {
    }

    #[Route('', name: 'admin_blog_category_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('admin/blog/category/index.html.twig', [
            'categories' => $this->categoryRepository->findAll(),
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
        if ($this->isCsrfTokenValid('delete'.$category->getId(), $request->request->get('_token'))) {
            $this->categoryRepository->remove($category, true);
            $this->addFlash('success', 'Category deleted.');
        }

        return $this->redirectToRoute('admin_blog_category_index');
    }
}
