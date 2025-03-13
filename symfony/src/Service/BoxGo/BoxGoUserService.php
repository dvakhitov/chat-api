<?php

namespace App\Service\BoxGo;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class BoxGoUserService
{
    public function __construct(
        private readonly string $boxgoUrl,
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function getBoxgoUser(string $token): ?array
    {
        $response = $this->httpClient->request('POST', $this->boxgoUrl, [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json',
            ]
        ]);

        if (!in_array($response->getStatusCode(), [200, 201])) {
            return null;
        }

        try {
            $userData = json_decode($response->getContent(), true);
        } catch (ClientExceptionInterface|RedirectionExceptionInterface|ServerExceptionInterface|TransportExceptionInterface $e) {
            $this->logger->error($e);

            return null;
        }

        return $userData;
    }
}
