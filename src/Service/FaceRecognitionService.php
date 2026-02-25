<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class FaceRecognitionService
{
    private $httpClient;
    private $faceServiceUrl;

    public function __construct(HttpClientInterface $httpClient, string $faceServiceUrl)
    {
        $this->httpClient = $httpClient;
        $this->faceServiceUrl = $faceServiceUrl;
    }

    /**
     * Enrolls a face in the python microservice.
     */
    public function enrollFace(string $imageBase64, string $userId): array
    {
        try {
            $response = $this->httpClient->request('POST', $this->faceServiceUrl . '/enroll', [
                'json' => [
                    'image' => $imageBase64,
                    'user_id' => $userId,
                ],
                'timeout' => 300,
            ]);

            return $response->toArray();
        }
        catch (\Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    /**
     * Recognizes a face from a base64 image.
     */
    public function recognizeFace(string $imageBase64): ?string
    {
        try {
            $response = $this->httpClient->request('POST', $this->faceServiceUrl . '/recognize', [
                'json' => [
                    'image' => $imageBase64,
                ],
                'timeout' => 300,
            ]);

            if ($response->getStatusCode() === 200) {
                $data = $response->toArray();
                return $data['user_id'] ?? null;
            }
        }
        catch (\Exception $e) {
        // Log error if needed
        }

        return null;
    }
}
