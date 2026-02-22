<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Service\AiTicketTaskService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/ticket')]
class TicketAiController extends AbstractController
{
    #[Route('/ai-tasks', name: 'api_ticket_ai_tasks', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function generateTasks(Request $request, AiTicketTaskService $aiService): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $title = $data['title'] ?? '';
        $description = $data['description'] ?? '';

        if (mb_strlen($description) < 10) {
            return new JsonResponse(['error' => 'Description is too short for AI analysis.'], 400);
        }

        $tasks = $aiService->generateTasks($title, $description);

        return new JsonResponse($tasks);
    }
}
