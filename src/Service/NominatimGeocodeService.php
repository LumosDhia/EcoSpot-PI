<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Geocoding via OpenStreetMap Nominatim (free, no API key).
 * Usage policy: https://operations.osmfoundation.org/policies/nominatim/
 */
class NominatimGeocodeService
{
    private const NOMINATIM_URL = 'https://nominatim.openstreetmap.org/search';

    public function __construct(
        private readonly HttpClientInterface $httpClient
    ) {
    }

    /**
     * Search for a place by name or address.
     *
     * @return list<array{display_name: string, lat: string, lon: string}>
     */
    public function search(string $query): array
    {
        $query = trim($query);
        if ($query === '') {
            return [];
        }

        try {
            $response = $this->httpClient->request('GET', self::NOMINATIM_URL, [
                'query' => [
                    'q' => $query,
                    'format' => 'json',
                    'limit' => 8,
                ],
                'headers' => [
                    'User-Agent' => 'EcoSpot/1.0 (Symfony; environmental ticket app)',
                    'Accept-Language' => 'en',
                ],
                'timeout' => 10,
            ]);
            $data = $response->toArray();
        } catch (\Throwable $e) {
            return [];
        }



        $results = [];
        foreach ($data as $item) {
            if (isset($item['lat'], $item['lon'], $item['display_name'])) {
                $results[] = [
                    'display_name' => (string) $item['display_name'],
                    'lat' => (string) $item['lat'],
                    'lon' => (string) $item['lon'],
                ];
            }
        }

        return $results;
    }

    /**
     * Reverse geocoding: get address from coordinates.
     * @return array<string, mixed>|null
     */
    public function reverse(string $lat, string $lon): ?array
    {
        try {
            $response = $this->httpClient->request('GET', 'https://nominatim.openstreetmap.org/reverse', [
                'query' => [
                    'lat' => $lat,
                    'lon' => $lon,
                    'format' => 'json',
                ],
                'headers' => [
                    'User-Agent' => 'EcoSpot/1.0 (Symfony; environmental ticket app)',
                    'Accept-Language' => 'en',
                ],
                'timeout' => 10,
            ]);
            return $response->toArray();
        } catch (\Throwable $e) {
            return null;
        }
    }
}
