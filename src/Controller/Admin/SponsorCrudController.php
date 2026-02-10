<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Sponsor;
use App\Form\SponsorType;
use App\Repository\SponsorRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/sponsors')]
class SponsorCrudController extends AbstractController
{
    private const UPLOAD_SPONSORS_DIR = 'images/sponsors';

    public function __construct(
        private readonly SponsorRepository $sponsorRepository,
        private readonly string $projectDir
    ) {
    }

    #[Route('', name: 'admin_sponsors_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('admin/sponsor/index.html.twig', [
            'sponsors' => $this->sponsorRepository->findAllOrderedByName(),
        ]);
    }

    #[Route('/new', name: 'admin_sponsors_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $sponsor = new Sponsor();
        $form = $this->createForm(SponsorType::class, $sponsor);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->handleImageUpload($form, $sponsor);
            $this->sponsorRepository->save($sponsor, true);

            $this->addFlash('success', 'Sponsor created successfully.');

            return $this->redirectToRoute('admin_sponsors_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/sponsor/new.html.twig', [
            'sponsor' => $sponsor,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'admin_sponsors_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(Sponsor $sponsor): Response
    {
        return $this->render('admin/sponsor/show.html.twig', [
            'sponsor' => $sponsor,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_sponsors_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(Request $request, Sponsor $sponsor): Response
    {
        $form = $this->createForm(SponsorType::class, $sponsor);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->handleImageUpload($form, $sponsor);
            $this->sponsorRepository->save($sponsor, true);

            $this->addFlash('success', 'Sponsor updated successfully.');

            return $this->redirectToRoute('admin_sponsors_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/sponsor/edit.html.twig', [
            'sponsor' => $sponsor,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'admin_sponsors_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(Request $request, Sponsor $sponsor): Response
    {
        $token = $request->request->getString('_token');
        if ($this->isCsrfTokenValid('delete' . $sponsor->getId(), $token)) {
            $this->sponsorRepository->remove($sponsor, true);
            $this->addFlash('success', 'Sponsor deleted successfully.');
        }

        return $this->redirectToRoute('admin_sponsors_index', [], Response::HTTP_SEE_OTHER);
    }

    private function handleImageUpload($form, Sponsor $sponsor): void
    {
        $file = $form->get('imageFile')->getData();
        if (!$file) {
            return;
        }

        $uploadDir = $this->projectDir . '/public/' . self::UPLOAD_SPONSORS_DIR;
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $safeName = uniqid('', true) . '.' . $file->guessExtension();
        $file->move($uploadDir, $safeName);
        $sponsor->setImage('/' . self::UPLOAD_SPONSORS_DIR . '/' . $safeName);
    }
}
