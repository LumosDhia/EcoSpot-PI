<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

class AiNgoSuggestionService
{
    private const API_URL = 'https://openrouter.ai/api/v1/chat/completions';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger,
        private readonly string $openRouterApiKey
    ) {
    }

    /**
     * @param string $ticketTitle
     * @param string $ticketDescription
     * @param array<int, array<string, mixed>> $ngos Array of arrays: [['id' => 1, 'name' => 'NGO Name', 'description' => '...']]
     * @return array<string, mixed>|null Returns ['suggested_ngo_id' => int, 'reason' => string] or null on failure
     */
    public function suggestNgo(string $ticketTitle, string $ticketDescription, array $ngos): ?array
    {
        if (empty($this->openRouterApiKey) || str_contains($this->openRouterApiKey, 'your_')) {
            $this->logger->warning('OpenRouter API key is not configured.');
            return null;
        }

        if (empty($ngos)) {
            return null;
        }

        $ngoListStr = json_encode($ngos, JSON_PRETTY_PRINT);

        $prompt = <<<EOT
You are an expert community coordinator. Your task is to match a reported issue (ticket) to the most appropriate NGO from the provided list, based on their descriptions and domains of expertise.

Ticket Title: $ticketTitle
Ticket Description: $ticketDescription

Available NGOs:
$ngoListStr

Analyze the ticket and the available NGOs. Reply ONLY with a JSON object containing:
- "suggested_ngo_id": The integer ID of the best matching NGO.
- "reason": A brief explanation (1-2 sentences) of why this NGO is the best fit.

Example:
{
  "suggested_ngo_id": 4,
  "reason": "This NGO specializes in ocean cleanup, making them ideal for handling the beach pollution ticket."
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
                $this->logger->error('OpenRouter API error (NGO Suggestion): ' . $response->getStatusCode() . ' - ' . $errorBody);
                return null;
            }

            $data = $response->toArray();
            $textResponse = $data['choices'][0]['message']['content'] ?? '{}';
            
            $this->logger->info('OpenRouter Raw NGO Suggestion: ' . $textResponse);

            $decoded = json_decode($textResponse, true);
            
            if (is_array($decoded) && isset($decoded['suggested_ngo_id'])) {
                return [
                    'suggested_ngo_id' => (int) $decoded['suggested_ngo_id'],
                    'reason' => $decoded['reason'] ?? ''
                ];
            }

            return null;

        } catch (\Exception $e) {
            $this->logger->error('Failed to suggest NGO: ' . $e->getMessage());
            return null;
        }
    }
}
