<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Fetches environmental news from The Guardian Open Platform API.
 * Register at https://open-platform.theguardian.com/access/ for an API key.
 */
class GuardianApiService
{
    private const API_URL = 'https://content.guardianapis.com/search';
    private const PAGE_SIZE = 8;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly ?string $apiKey = null
    ) {
    }

    /**
     * @return array<int, array{title: string, url: string, date: string, excerpt: string, thumbnail: string|null}>
     */
    public function getEnvironmentalNews(): array
    {
        if ($this->apiKey === null || $this->apiKey === '') {
            return [];
        }

        try {
            $response = $this->httpClient->request('GET', self::API_URL, [
                'query' => [
                    'api-key' => $this->apiKey,
                    'q' => 'environment',
                    'page-size' => self::PAGE_SIZE,
                    'show-fields' => 'headline,trailText,thumbnail',
                    'order-by' => 'newest',
                ],
                'timeout' => 5,
            ]);
            $data = $response->toArray();
        } catch (\Throwable) {
            return [];
        }

        $results = $data['response']['results'] ?? [];
        $items = [];

        foreach ($results as $item) {
            $fields = $item['fields'] ?? [];
            $thumb = $fields['thumbnail'] ?? null;
            if (is_array($thumb)) {
                $thumb = $thumb['url'] ?? $thumb['source'] ?? null;
            }
            $items[] = [
                'title' => $fields['headline'] ?? $item['webTitle'] ?? 'Untitled',
                'url' => $item['webUrl'] ?? '#',
                'date' => isset($item['webPublicationDate'])
                    ? (new \DateTimeImmutable($item['webPublicationDate']))->format('d M Y')
                    : '',
                'excerpt' => $fields['trailText'] ?? '',
                'thumbnail' => $thumb ? (string) $thumb : null,
            ];
        }

        return $items;
    }
}
