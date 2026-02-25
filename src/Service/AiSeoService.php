<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Blog\Article\Article;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

class AiSeoService
{
    private const API_URL = 'https://openrouter.ai/api/v1/chat/completions';

    public function __construct(
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger,
        private string $openRouterApiKey
    ) {
    }

    /**
     * Generates SEO elements for an article using OpenRouter (Free Models available).
     * Returns an array with 'title', 'description', and 'keywords'.
     */
    public function generateSeoElements(Article $article): array
    {
        if (empty($this->openRouterApiKey) || str_contains($this->openRouterApiKey, 'your_')) {
            $this->logger->warning('OpenRouter API key is not configured. Skipping AI SEO generation.');
            return [];
        }

        $title = $article->getTitle();
        $content = strip_tags($article->getContent());
        $contentChunk = mb_substr($content, 0, 3000);

        $prompt = <<<EOT
You are a content expert. Analyze the following article content to generate high-quality metadata. 
Reply ONLY with a JSON object containing carefully crafted:
- "title": A catchy title (max 60 chars).
- "description": A compelling summary description (max 160 chars).
- "keywords": A comma-separated list of 5-10 relevant keywords.

Article Title: {$title}
Article Content: {$contentChunk}
EOT;

        try {
            $response = $this->httpClient->request('POST', self::API_URL, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->openRouterApiKey,
                    'Content-Type' => 'application/json',
                    'HTTP-Referer' => 'http://localhost:8000', // Required by OpenRouter
                    'X-Title' => 'EcoSpot Project', // Optional identification
                ],
                'json' => [
                    'model' => 'openrouter/auto', 
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'You are an SEO assistant. You must output ONLY valid JSON.'
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
                $this->logger->error('OpenRouter API error: ' . $response->getStatusCode() . ' ' . $response->getContent(false));
                return [];
            }

            $data = $response->toArray();
            $textResponse = $data['choices'][0]['message']['content'] ?? '';
            
            if (empty($textResponse)) {
                return [];
            }

            return json_decode($textResponse, true) ?? [];

        } catch (\Exception $e) {
            $this->logger->error('Failed to generate SEO elements via OpenRouter: ' . $e->getMessage());
            return [];
        }
    }

    public function generateTitleIdeas(Article $article): array
    {
        if (empty($this->openRouterApiKey) || str_contains($this->openRouterApiKey, 'your_')) {
            return [];
        }

        $title = $article->getTitle();
        $content = strip_tags($article->getContent());
        $contentChunk = mb_substr($content, 0, 3000);

        $prompt = <<<EOT
You are a creative editor. Analyze the following article content to generate 5 DIFFERENT catchy and engaging titles (max 60 chars each).
Reply ONLY with a JSON object containing a key "titles" which is an array of 5 strings.

Article Title: {$title}
Article Content: {$contentChunk}
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
                        ['role' => 'system', 'content' => 'You are an SEO assistant. You must output ONLY valid JSON.'],
                        ['role' => 'user', 'content' => $prompt]
                    ],
                    'response_format' => ['type' => 'json_object']
                ]
            ]);

            $data = $response->toArray();
            $textResponse = $data['choices'][0]['message']['content'] ?? '';
            $json = json_decode($textResponse, true);
            
            return $json['titles'] ?? $json ?? [];

        } catch (\Exception $e) {
            $this->logger->error('Failed to generate title ideas: ' . $e->getMessage());
            return [];
        }
    }
}
