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
}
