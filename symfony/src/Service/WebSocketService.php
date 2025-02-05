<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\SerializerInterface;

readonly class WebSocketService
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private string $webSocketUrl,
        private LoggerInterface $logger,
        private SerializerInterface $serializer
    ) {
        // Логирование для проверки значения
        $this->logger->info("WebSocket URL: {$this->webSocketUrl}");
    }

    public function send(array|object $data, int $userId): void
    {
        try {
            if (!is_array($data)) {
                $data = $this->serializer->normalize($data, null, ['groups' => ['websocket']]);
            }

            $messageData = [
                'recipient' => $userId,
                'type' => $data['type'] ?? 'message', // Убедитесь, что есть значение по умолчанию
                'data' => $data,
            ];

            $response = $this->httpClient->request('POST', $this->webSocketUrl . '/send', [
                'json' => $messageData,
            ]);

            $this->logger->debug('WebSocket server response : ' . (string)$userId);
            $statusCode = $response->getStatusCode();
            $content = $response->getContent(false); // Не выбрасывает исключение для статусов 400 и выше

            if (200 !== $statusCode) {
                $this->logger->error(
                    'WebSocketError: Response is not OK. Status code: {statusCode}. Content: {content}',
                    [
                        'statusCode' => $statusCode,
                        'content' => $content,
                    ]
                );

                return;
            }
            $this->logger->info('WebSocket server response', [
                'statusCode' => $statusCode,
                'content' => $content,
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Error sending data to WebSocket server', [
                'exception' => $e,
            ]);
        }
    }

    private function prepareDataToSend(array $data)
    {
        foreach ($data as $key => $datum) {
            if (is_object($datum)) {
                $data[$key] = (array)$datum;
            }
        }

        return $data;
    }
} 
