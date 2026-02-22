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
     * Generates a list of suggested tasks for a ticket.
     * Returns an array of strings.
     */
    public function generateTasks(string $title, string $description): array
    {
        if (empty($this->openRouterApiKey) || str_contains($this->openRouterApiKey, 'your_')) {
            $this->logger->warning('OpenRouter API key is not configured.');
            return [];
        }

        $prompt = <<<EOT
You are an environmental community organizer. Based on the ticket title and description below, generate a list of 3-7 short, actionable, and practical tasks (instructions) that a volunteer can follow to resolve the issue.

Ticket Title: $title
Ticket Description: $description

Reply ONLY with a raw JSON array of strings. Example: ["Collect the plastic waste", "Take photos of the progress", "Dispose of waste at the nearest recycling center"]
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
                    'model' => 'google/learnlm-1.5-pro-experimental:free', // Using a fast/free model
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'You are an assistant that outputs ONLY a JSON array of strings.'
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
                $this->logger->error('OpenRouter API error: ' . $response->getStatusCode());
                return [];
            }

            $data = $response->toArray();
            $textResponse = $data['choices'][0]['message']['content'] ?? '[]';
            
            // Sometimes models wrap JSON in markdown or just return the array
            $decoded = json_decode($textResponse, true);
            
            if (is_array($decoded)) {
                // If the model returned an object with a key, or just the array
                return isset($decoded['tasks']) ? $decoded['tasks'] : (isset($decoded[0]) ? $decoded : []);
            }

            return [];

        } catch (\Exception $e) {
            $this->logger->error('Failed to generate tasks: ' . $e->getMessage());
            return [];
        }
    }
}
