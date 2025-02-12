<?php

namespace App\MessageHandler;

use App\DTO\NotificationMessage\MessageRecipientNotificationMessageDTO;
use App\DTO\NotificationMessage\MessageSenderNotificationMessageDTO;
use App\Event\NotificationsSentEvent;
use App\Message\NotificationMessage;
use App\Service\WebSocketService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[AsMessageHandler]
final readonly class NotificationMessageHandler
{
    public function __construct(
        private WebSocketService $webSocketService,
        private EventDispatcherInterface $dispatcher,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(NotificationMessage $message)
    {
        try {
            $this->webSocketService->send(
                $this->objectsToArray($message->data),
                $message->notificationRecipientId
            );

            if ($message->data instanceof MessageRecipientNotificationMessageDTO) {
                $this->sendCountChats(
                    $message->notificationRecipientId,
                    $message->data->lastMessage->id,
                    $message->isSystem
                );
            }
        } catch (\Throwable $e) {
           $this->logger->error(sprintf('Error sending data to WebSocket server: %s', $e->getMessage() ), []);
        }

    }

    private function objectsToArray(mixed $data): array
    {
        $result = [];
        try {
            foreach ($data as $key => $value) {
                if (is_object($value)) {
                    $result[$key] = $this->objectToArray($value);
                    continue;
                }

                $result[$key] = $value;
            }
        } catch (\Throwable $th) {
            $this->logger->error($th->getMessage(), [
                'exception' => $th,
            ]);
        }

        return $result;
    }

    private function objectToArray(object $object): array
    {
        return array_map(function ($item) {
            return $item;
        }, (array)$object);
    }

    private function sendCountChats(int $recipientId, int $messageId, $isSystem): void
    {
        $event = new NotificationsSentEvent(
            recipientId: $recipientId,
            messageId: $messageId,
            isSystem: $isSystem
        );

        $this->dispatcher->dispatch($event);
    }
} 
