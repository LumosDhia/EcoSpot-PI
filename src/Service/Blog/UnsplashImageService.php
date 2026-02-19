<?php

declare(strict_types=1);

namespace App\Service\Blog;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Psr\Log\LoggerInterface;

class UnsplashImageService
{
    private const DEFAULT_PLACEHOLDER = 'https://images.unsplash.com/photo-1542601906990-b4d3fb778b09?w=800';
    private const CACHE_KEY = 'unsplash_nature_tunisia_v1';
    private const CACHE_TTL = 3600; // 1 hour

    public function __construct(
        private HttpClientInterface $httpClient,
        private CacheInterface $cache,
        private LoggerInterface $logger,
        private string $unsplashAccessKey
    ) {
    }

    public function getRandomNatureImage(): string
    {
        if ($this->unsplashAccessKey === 'your_access_key_here' || empty($this->unsplashAccessKey)) {
            return self::DEFAULT_PLACEHOLDER;
        }

        try {
            return $this->cache->get(self::CACHE_KEY, function (ItemInterface $item) {
                $item->expiresAfter(self::CACHE_TTL);

                $response = $this->httpClient->request('GET', 'https://api.unsplash.com/photos/random', [
                    'query' => [
                        'query' => 'nature,Tunisia',
                        'orientation' => 'landscape',
                    ],
                    'headers' => [
                        'Authorization' => 'Client-ID ' . $this->unsplashAccessKey,
                    ],
                ]);

                if ($response->getStatusCode() !== 200) {
                    $this->logger->error('Unsplash API error: ' . $response->getStatusCode());
                    return self::DEFAULT_PLACEHOLDER;
                }

                $data = $response->toArray();
                return $data['urls']['regular'] ?? self::DEFAULT_PLACEHOLDER;
            });
        } catch (\Exception $e) {
            $this->logger->error('Failed to fetch image from Unsplash: ' . $e->getMessage());
            return self::DEFAULT_PLACEHOLDER;
        }
    }

    public function searchImages(string $query, int $page = 1, int $perPage = 12): array
    {
        if ($this->unsplashAccessKey === 'your_access_key_here' || empty($this->unsplashAccessKey)) {
            return [];
        }

        try {
            $response = $this->httpClient->request('GET', 'https://api.unsplash.com/search/photos', [
                'query' => [
                    'query' => $query,
                    'page' => $page,
                    'per_page' => $perPage,
                    'orientation' => 'landscape',
                ],
                'headers' => [
                    'Authorization' => 'Client-ID ' . $this->unsplashAccessKey,
                ],
            ]);

            if ($response->getStatusCode() !== 200) {
                $this->logger->error('Unsplash Search API error: ' . $response->getStatusCode());
                return [];
            }

            return $response->toArray();
        } catch (\Exception $e) {
            $this->logger->error('Failed to search images on Unsplash: ' . $e->getMessage());
            return [];
        }
    }
}
