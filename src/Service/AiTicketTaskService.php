<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

class AiTicketTaskService
{
    private const API_URL = 'https://openrouter.ai/api/v1/chat/completions';

    public function __construct(
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger,
        private string $openRouterApiKey
    ) {
    }

    /**
     * Generates suggested tasks and priority for a ticket.
     * Returns an array with 'tasks' (array of objects) and 'priority' (string).
     */
    public function generateTasks(string $title, string $description): array
    {
        if (empty($this->openRouterApiKey) || str_contains($this->openRouterApiKey, 'your_')) {
            $this->logger->warning('OpenRouter API key is not configured.');
            return [];
        }

        $prompt = <<<EOT
You are an environmental community organizer. Based on the ticket title and description below:
1. Generate a list of 3-7 short, actionable, and practical tasks (instructions).
2. For each task, assess its difficulty (EASY, MEDIUM, or HARD).
3. Suggest an overall priority for the ticket (LOW, MEDIUM, HIGH, or URGENT).

Ticket Title: $title
Ticket Description: $description

Reply ONLY with a JSON object containing:
- "tasks": An array of objects, each with "description" and "difficulty" (EASY, MEDIUM, or HARD).
- "suggested_priority": A string (LOW, MEDIUM, HIGH, or URGENT).

Example: 
{
  "tasks": [
    {"description": "Collect plastic waste", "difficulty": "EASY"},
    {"description": "Contact local recycling center", "difficulty": "MEDIUM"}
  ],
  "suggested_priority": "HIGH"
}
EOT;

        try {
            $response = $this->httpClient->request('POST', self::API_URL, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->openRouterApiKey,
                    'Content-Type' => 'application/json',
                    'HTTP-Referer' => 'http://localhost:8000',
                    'X-Title' => 'EcoSpot Project',
                ],
                'json' => [
                    'model' => 'openrouter/auto', 
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'You are an assistant that outputs ONLY a JSON object with the requested keys.'
                        ],
                        [
                            'role' => 'user',
                            'content' => $prompt
                        ]
                    ],
                    'response_format' => ['type' => 'json_object']
                ]
            ]);

            if ($response->getStatusCode() !== 200) {
                $errorBody = $response->getContent(false);
                $this->logger->error('OpenRouter API error: ' . $response->getStatusCode() . ' - ' . $errorBody);
                return [];
            }

            $data = $response->toArray();
            $textResponse = $data['choices'][0]['message']['content'] ?? '{}';
            
            $this->logger->info('OpenRouter Raw Response: ' . $textResponse);

            $decoded = json_decode($textResponse, true);
            
            if (is_array($decoded)) {
                return [
                    'tasks' => $decoded['tasks'] ?? [],
                    'priority' => $decoded['suggested_priority'] ?? 'MEDIUM'
                ];
            }

            return [];

        } catch (\Exception $e) {
            $this->logger->error('Failed to generate tasks: ' . $e->getMessage());
            return [];
        }
    }
}
