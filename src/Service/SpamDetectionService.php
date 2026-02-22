<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

class SpamDetectionService
{
    private const API_URL = 'https://openrouter.ai/api/v1/chat/completions';

    public function __construct(
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger,
        private string $openRouterApiKey
    ) {
    }

    /**
     * Checks if a ticket content is spam or out of context.
     * Returns true if it's spam, false otherwise.
     */
    public function isSpam(string $title, string $description): bool
    {
        if (empty($this->openRouterApiKey) || str_contains($this->openRouterApiKey, 'your_')) {
            $this->logger->warning('OpenRouter API key is not configured for spam detection.');
            return false;
        }

        $prompt = <<<EOT
You are an environmental community moderator. Analyze the following ticket submitted by a citizen.

Ticket Title: $title
Ticket Description: $description

A ticket should be flagged as SPAM (true) if:
1. It is clearly nonsensical or gibberish.
2. It contains offensive, hateful, or prohibited content.
3. It is COMPLETELY unrelated to environmental issues, community cleanup, or ecological protection (e.g., trying to sell a product, talking about unrelated politics, etc.).

A ticket should NOT be flagged as spam (false) if:
1. It reports an environmental issue (trash, pollution, dead trees, recycling needs).
2. It requests information about community green events.

Reply ONLY with a JSON object containing:
- "is_spam": A boolean value (true or false).
- "reason": A short one-sentence explanation for the decision.

Example: 
{
  "is_spam": false,
  "reason": "The ticket reports a valid waste accumulation issue."
}
EOT;

        try {
            $response = $this->httpClient->request('POST', self::API_URL, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->openRouterApiKey,
                    'Content-Type' => 'application/json',
                    'HTTP-Referer' => 'http://localhost:8000',
                    'X-Title' => 'EcoSpot Project Moderator',
                ],
                'json' => [
                    'model' => 'openrouter/auto', 
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'You are a precise environmental content moderator. Output ONLY valid JSON.'
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
                $this->logger->error('Spam Detection API error: ' . $response->getStatusCode() . ' - ' . $errorBody);
                return false;
            }

            $data = $response->toArray();
            $textResponse = $data['choices'][0]['message']['content'] ?? '{}';
            
            $this->logger->info('Spam Detection Raw Response: ' . $textResponse);

            $decoded = json_decode($textResponse, true);
            
            if (isset($decoded['is_spam'])) {
                return (bool) $decoded['is_spam'];
            }

            return false;

        } catch (\Exception $e) {
            $this->logger->error('Failed to perform spam detection: ' . $e->getMessage());
            return false;
        }
    }
}
