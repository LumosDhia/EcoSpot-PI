<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Evenement;
use App\Form\EvenementType;
use App\Repository\EvenementRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/events')]
class EvenementCrudController extends AbstractController
{
    private const UPLOAD_EVENTS_DIR = 'images/events';

    public function __construct(
        private readonly EvenementRepository $evenementRepository,
        private readonly string $projectDir
    ) {
    }

    #[Route('', name: 'admin_events_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('admin/evenement/index.html.twig', [
            'events' => $this->evenementRepository->findAllOrderedByDate(),
        ]);
    }

    #[Route('/new', name: 'admin_events_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $evenement = new Evenement();
        $form = $this->createForm(EvenementType::class, $evenement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->handleImageUpload($form, $evenement);
            $this->evenementRepository->save($evenement, true);

            $this->addFlash('success', 'Event created successfully.');

            return $this->redirectToRoute('admin_events_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/evenement/new.html.twig', [
            'event' => $evenement,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'admin_events_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(Evenement $evenement): Response
    {
        return $this->render('admin/evenement/show.html.twig', [
            'event' => $evenement,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_events_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(Request $request, Evenement $evenement): Response
    {
        $form = $this->createForm(EvenementType::class, $evenement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->handleImageUpload($form, $evenement);
            $this->evenementRepository->save($evenement, true);

            $this->addFlash('success', 'Event updated successfully.');

            return $this->redirectToRoute('admin_events_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/evenement/edit.html.twig', [
            'event' => $evenement,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'admin_events_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(Request $request, Evenement $evenement): Response
    {
        $token = $request->request->getString('_token');
        if ($this->isCsrfTokenValid('delete' . $evenement->getId(), $token)) {
            $this->evenementRepository->remove($evenement, true);
            $this->addFlash('success', 'Event deleted successfully.');
        }

        return $this->redirectToRoute('admin_events_index', [], Response::HTTP_SEE_OTHER);
    }

    private function handleImageUpload($form, Evenement $evenement): void
    {
        $file = $form->get('imageFile')->getData();
        if (!$file) {
            return;
        }

        $uploadDir = $this->projectDir . '/public/' . self::UPLOAD_EVENTS_DIR;
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $safeName = uniqid('', true) . '.' . $file->guessExtension();
        $file->move($uploadDir, $safeName);
        $evenement->setImage('/' . self::UPLOAD_EVENTS_DIR . '/' . $safeName);
    }
}
