<?php

namespace App\Service;

use App\DTO\ChatMessageDtoInterface;
use App\DTO\NotificationMessage\AbstractNotificationMessageDTO;
use App\DTO\NotificationMessage\MessageRecipientNotificationMessageDTO;
use App\DTO\NotificationMessage\MessageSenderNotificationMessageDTO;
use App\Event\NotificationsSentEvent;
use App\Message\NotificationMessage;
use App\Service\MessageHandler\ChatMessageHandler;
use App\Service\MessageHandler\SystemMessageHandler;
use App\Service\MessageHandler\MessageHandlerInterface;
use App\Service\WebSocket\WebSocketClient;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class MessageProcessor
{
    private array $handlers;

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly EventDispatcherInterface $dispatcher,
        private readonly WebSocketClient $webSocketClient,
        ChatMessageHandler $chatHandler,
        SystemMessageHandler $systemMessageHandler
    ) {
        $this->handlers = [
            'message' => $chatHandler,
            'system' => $systemMessageHandler
        ];
    }

    public function process(ChatMessageDtoInterface $messageData): void
    {
        $type = $messageData->type ?? 'unknown';

        if (!isset($this->handlers[$type])) {
            throw new \InvalidArgumentException("Handler for message type '{$type}' not found");
        }

        /** @var MessageHandlerInterface $handler */
        $handler = $this->handlers[$type];

        try {
            $result = $handler->handle($messageData);
        } catch (\Throwable $e) {
            dd($e);
            $this->logger->error('Error processing message: ' . $e->getMessage(), []);
            return;
        }
        foreach ($result->notifications as $item) {
            if ($item instanceof MessageSenderNotificationMessageDTO) {
                $notificationRecipient = $item->lastMessage->senderId;
            } elseif ($item instanceof MessageRecipientNotificationMessageDTO) {
                $notificationRecipient = $messageData->recipient;
            }

            $message = new NotificationMessage($item, $notificationRecipient, $messageData->type === 'system');

            try {
                $this->webSocketClient->send($message->data, $message->notificationRecipientId);
            } catch (\Throwable $e) {
                $this->logger->error('Error processing message: ' . $e->getMessage(), [
                    'exception' => $e,
                    'messageData' => $messageData
                ]);
            }
            if ($message->data instanceof MessageRecipientNotificationMessageDTO) {
                $event = new NotificationsSentEvent(
                    recipientId: $message->notificationRecipientId,
                    messageId: $message->data->lastMessage->id,
                    isSystem: $message->isSystem
                );

                $this->dispatcher->dispatch($event);
            }
        }
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
