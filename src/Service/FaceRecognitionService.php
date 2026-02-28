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

            $data = $response->toArray();
            if (isset($data['status']) && $data['status'] === 'error') {
                throw new \Exception($data['message'] ?? 'Unknown error from face service');
            }

            return $data;
        }
        catch (\Exception $e) {
            // Log the error for debugging
            return [
                'status' => 'error', 
                'message' => 'Face service enrollment failed: ' . $e->getMessage(),
                'technical_detail' => 'Attempted URL: ' . $this->faceServiceUrl . '/enroll. Ensure the Python server is running on port 8001. Check face_service.log for server-side errors.',
                'error_type' => get_class($e)
            ];
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
            // Log error if needed: $e->getMessage()
            // This might happen if the server is down or returns 401
        }

        return null;
    }
}
