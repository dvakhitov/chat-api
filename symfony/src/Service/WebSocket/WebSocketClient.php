<?php

namespace App\Service\WebSocket;

use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class WebSocketClient
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private string $wsUrl,
        private SerializerInterface $serializer
    ) {}

    public function send(object $data, int $userId): void
    {
        $this->httpClient->request('POST', $this->wsUrl . '/send', [
            'json' => [
                'type' => 'send',
                'recipient' => $userId,
                'data' => json_decode(
                    $this->serializer->serialize($data, 'json'),
                    true
                )
            ],
            'verify_peer' => false,
            'verify_host' => false
        ]);
    }
} 
