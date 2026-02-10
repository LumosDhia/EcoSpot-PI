<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * 7-day weather forecast via Open-Meteo (free, no API key).
 * https://open-meteo.com/
 */
class OpenMeteoWeatherService
{
    private const API_URL = 'https://api.open-meteo.com/v1/forecast';

    public function __construct(
        private readonly HttpClientInterface $httpClient
    ) {
    }

    /**
     * Get daily forecast for the next 7 days at given coordinates.
     *
     * @return list<array{date: string, temp_max: float, temp_min: float, weather_code: int, description: string}>
     */
    public function getWeeklyForecast(float $latitude, float $longitude): array
    {
        try {
            $response = $this->httpClient->request('GET', self::API_URL, [
                'query' => [
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'daily' => 'temperature_2m_max,temperature_2m_min,weather_code',
                    'timezone' => 'auto',
                    'forecast_days' => 7,
                ],
                'timeout' => 5,
            ]);
            $data = $response->toArray();
        } catch (\Throwable) {
            return [];
        }

        $daily = $data['daily'] ?? null;
        if (!is_array($daily) || empty($daily['time'])) {
            return [];
        }

        $times = $daily['time'];
        $maxTemps = $daily['temperature_2m_max'] ?? array_fill(0, count($times), null);
        $minTemps = $daily['temperature_2m_min'] ?? array_fill(0, count($times), null);
        $codes = $daily['weather_code'] ?? array_fill(0, count($times), 0);

        $result = [];
        foreach ($times as $i => $date) {
            $code = (int) ($codes[$i] ?? 0);
            $result[] = [
                'date' => (string) $date,
                'temp_max' => $maxTemps[$i] !== null ? (float) $maxTemps[$i] : 0.0,
                'temp_min' => $minTemps[$i] !== null ? (float) $minTemps[$i] : 0.0,
                'weather_code' => $code,
                'description' => $this->weatherCodeToDescription($code),
            ];
        }

        return $result;
    }

    private function weatherCodeToDescription(int $code): string
    {
        return match (true) {
            $code === 0 => 'Clear',
            $code === 1 => 'Mainly clear',
            $code === 2 => 'Partly cloudy',
            $code === 3 => 'Overcast',
            $code >= 45 && $code <= 48 => 'Fog',
            $code >= 51 && $code <= 57 => 'Drizzle',
            $code >= 61 && $code <= 67 => 'Rain',
            $code >= 71 && $code <= 77 => 'Snow',
            $code >= 80 && $code <= 82 => 'Rain showers',
            $code >= 85 && $code <= 86 => 'Snow showers',
            $code >= 95 => 'Thunderstorm',
            default => 'Unknown',
        };
    }
}
