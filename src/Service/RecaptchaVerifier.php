<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class RecaptchaVerifier
{
    private const SITEVERIFY_URL = 'https://www.google.com/recaptcha/api/siteverify';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $secretKey
    ) {
    }

    public function verify(string $token): bool
    {
        if ($token === '') {
            return false;
        }

        try {
            $response = $this->httpClient->request('POST', self::SITEVERIFY_URL, [
                'body' => [
                    'secret'   => $this->secretKey,
                    'response' => $token,
                ],
            ]);
            $data = $response->toArray();
            return ($data['success'] ?? false) === true;
        } catch (\Throwable) {
            return false;
        }
    }
}
