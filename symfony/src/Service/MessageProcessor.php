<?php

namespace App\Service;

use App\DTO\MessageHandlerResultDTO;
use App\DTO\NotificationMessage\SenderNotificationMessageDTO;
use App\DTO\ProcessMessageDTO;
use App\Service\MessageHandler\ChatMessageHandler;
use App\Service\MessageHandler\SystemMessageHandler;
use App\Service\WebSocket\WebSocketClient;
use Psr\Log\LoggerInterface;

class MessageProcessor
{
    private array $handlers;

    public function __construct(
        private readonly WebSocketClient $wsClient,
        private readonly LoggerInterface $logger,
        ChatMessageHandler $chatHandler,
        SystemMessageHandler $systemMessageHandler
    ) {
        // Регистрируем обработчики для разных типов сообщений
        $this->handlers = [
            'message' => $chatHandler,
            'system' => $systemMessageHandler
        ];
    }

    public function process(ProcessMessageDTO $messageData): void
    {
        try {
            $type = $messageData->type ?? 'unknown';

            if (!isset($this->handlers[$type])) {
                throw new \InvalidArgumentException("Unknown message type: $type");
            }

            /** @var MessageHandlerResultDTO $result */
            $result = $this->handlers[$type]->handle($messageData);

            foreach ($result->notifications as $message) {
                if ($message instanceof SenderNotificationMessageDTO) {
                    $this->wsClient->send(
                        $message, $messageData->sender);
                }
            }
//            // Отправляем результат через WebSocket
//            if (isset($messageData['recipient'])) {
//                $this->wsClient->send($result, $messageData['recipient']);
//            }

        } catch (\Throwable $e) {
            dd($e);
            $this->logger->error('Error processing message: ' . $e->getMessage(), [
                'exception' => $e,
                'messageData' => $messageData
            ]);

            // Отправляем сообщение об ошибке
            $this->wsClient->send([
                'type' => 'error',
                'message' => 'Error processing message'
            ], $messageData['sender']);
        }
    }
} 
