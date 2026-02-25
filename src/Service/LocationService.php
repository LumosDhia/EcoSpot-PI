<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class LocationService
{

    public function __construct(
        private readonly HttpClientInterface $httpClient
    ) {
    }

    /**
     * Geocodes an address into latitude and longitude using Nominatim (OpenStreetMap).
     * Tries multiple query strategies for best results.
     *
     * @return array{lat: float, lng: float}|null
     */
    public function geocode(string $address): ?array
    {
        // Build multiple search queries to try (most specific first, then fallbacks)
        $queries = [
            $address,                          // Full address as-is
        ];

        // Extract parts for fallback queries
        $parts = array_map('trim', explode(',', $address));
        if (count($parts) >= 2) {
            $queries[] = end($parts);          // Just the city part
            $queries[] = reset($parts);        // Just the street/address part
        }

        foreach ($queries as $query) {
            $query = trim($query);
            if ($query === '') {
                continue;
            }

            $result = $this->nominatimSearch($query);
            if ($result !== null) {
                return $result;
            }
        }

        return null;
    }

    /**
     * Performs a single Nominatim search query.
     */
    private function nominatimSearch(string $query): ?array
    {
        try {
            $response = $this->httpClient->request('GET', 'https://nominatim.openstreetmap.org/search', [
                'query' => [
                    'q' => $query,
                    'format' => 'json',
                    'limit' => 1,
                    'countrycodes' => 'tn',
                ],
                'headers' => [
                    'User-Agent' => 'EcoSpot/1.0 (Symfony; environmental ticket app)',
                    'Accept-Language' => 'en',
                ],
                'timeout' => 10,
            ]);

            $data = $response->toArray();

            if (isset($data[0]['lat'], $data[0]['lon'])) {
                return [
                    'lat' => (float) $data[0]['lat'],
                    'lng' => (float) $data[0]['lon'],
                ];
            }
        } catch (\Throwable $e) {
            // Continue to next query
        }

        return null;
    }

    /**
     * Calculates the distance between two points in kilometers using the Haversine formula.
     */
    public function calculateDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371; // km

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLng / 2) * sin($dLng / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}
