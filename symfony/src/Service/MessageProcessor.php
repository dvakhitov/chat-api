<?php

namespace App\Service;

use App\DTO\ChatMessageDtoInterface;
use App\DTO\NotificationMessage\AbstractNotificationMessageDTO;
use App\Event\NotificationSentEvent;
use App\Message\NotificationMessage;
use App\Service\MessageHandler\ChatMessageHandler;
use App\Service\MessageHandler\SystemMessageHandler;
use App\Service\MessageHandler\MessageHandlerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class MessageProcessor
{
    private array $handlers;

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly MessageBusInterface $messageBus,
        ChatMessageHandler $chatHandler,
        SystemMessageHandler $systemMessageHandler
    ) {
        // Регистрируем обработчики для разных типов сообщений
        $this->handlers = [
            'message' => $chatHandler,
            'system' => $systemMessageHandler
        ];
    }

    public function process(ChatMessageDtoInterface $messageData): void
    {
        try {
            $type = $messageData->type ?? 'unknown';

            if (!isset($this->handlers[$type])) {
                throw new \InvalidArgumentException("Handler for message type '{$type}' not found");
            }

            /** @var MessageHandlerInterface $handler */
            $handler = $this->handlers[$type];

            $result = $handler->handle($messageData);

            $partnersIds = $this->getPartnersIds($result->notifications);
            foreach ($result->notifications as $item) {
                $message = new NotificationMessage($item, $this->getSenderId($partnersIds, $item));
                $this->messageBus->dispatch($message);
            }
            $event = new NotificationSentEvent(5, );
        } catch (\Throwable $e) {
            $this->logger->error('Error processing message: ' . $e->getMessage(), [
                'exception' => $e,
                'messageData' => $messageData
            ]);
            // Обработка ошибки
        }
    }

    /**
     * @param AbstractNotificationMessageDTO[] $result
     * @return array
     */
    private function getPartnersIds(array $result): array
    {
        $ids = [];
        foreach ($result as $item) {
            $ids[] = $item->chatPartner->userId;
        }

        return $ids;
    }

    private function getSenderId(array $partnersIds, AbstractNotificationMessageDTO $notification): int
    {
        foreach ($partnersIds as $partnersId) {
            if ($notification->chatPartner->userId !== $partnersId) {
                return $partnersId;
            }
        }

        throw new \RuntimeException('Sender not found: ' . __METHOD__);
    }
} 
