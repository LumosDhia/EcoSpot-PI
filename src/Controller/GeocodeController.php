<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\NominatimGeocodeService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class GeocodeController extends AbstractController
{
    public function __construct(
        private readonly NominatimGeocodeService $geocodeService
    ) {
    }

    #[Route('/api/geocode', name: 'api_geocode', methods: ['GET'])]
    public function search(Request $request): JsonResponse
    {
        $q = $request->query->get('q', '');
        if (strlen(trim($q)) < 2) {
            return new JsonResponse([]);
        }

        $results = $this->geocodeService->search($q);

        return new JsonResponse($results);
    }

    #[Route('/api/geocode/reverse', name: 'api_geocode_reverse', methods: ['GET'])]
    public function reverse(Request $request): JsonResponse
    {
        $lat = $request->query->get('lat');
        $lon = $request->query->get('lon');

        if (!$lat || !$lon) {
            return new JsonResponse(['error' => 'Missing coordinates'], 400);
        }

        $result = $this->geocodeService->reverse((string) $lat, (string) $lon);

        return new JsonResponse($result ?? ['error' => 'Not found'], $result ? 200 : 404);
    }
}
