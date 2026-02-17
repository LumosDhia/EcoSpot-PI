<?php

declare(strict_types=1);

namespace App\Controller\Admin\Blog;

use App\Entity\Tag;
use App\Form\TagType;
use App\Repository\TagRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/blog/tag')]
class TagCrudController extends AbstractController
{
    public function __construct(
        private readonly TagRepository $tagRepository
    ) {
    }

    #[Route('', name: 'admin_blog_tag_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('admin/blog/tag/index.html.twig', [
            'tags' => $this->tagRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'admin_blog_tag_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $tag = new Tag();
        $form = $this->createForm(TagType::class, $tag);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->tagRepository->save($tag, true);
            $this->addFlash('success', 'Tag created successfully.');
            return $this->redirectToRoute('admin_blog_tag_index');
        }

        return $this->render('admin/blog/tag/new.html.twig', [
            'tag' => $tag,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_blog_tag_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Tag $tag): Response
    {
        $form = $this->createForm(TagType::class, $tag);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->tagRepository->save($tag, true);
            $this->addFlash('success', 'Tag updated successfully.');
            return $this->redirectToRoute('admin_blog_tag_index');
        }

        return $this->render('admin/blog/tag/edit.html.twig', [
            'tag' => $tag,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'admin_blog_tag_delete', methods: ['POST'])]
    public function delete(Request $request, Tag $tag): Response
    {
        if ($this->isCsrfTokenValid('delete'.$tag->getId(), $request->request->get('_token'))) {
            $this->tagRepository->remove($tag, true);
            $this->addFlash('success', 'Tag deleted.');
        }

        return $this->redirectToRoute('admin_blog_tag_index');
    }
}
