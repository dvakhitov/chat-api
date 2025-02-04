<?php

namespace App\MessageHandler;

use App\DTO\NotificationMessage\SenderNotificationMessageDTO;
use App\Event\NotificationsSentEvent;
use App\Message\NotificationMessage;
use App\Service\WebSocketService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[AsMessageHandler]
readonly class NotificationMessageHandler
{
    public function __construct(
        private WebSocketService $webSocketService,
        private EventDispatcherInterface $dispatcher,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(NotificationMessage $message)
    {
        $this->webSocketService->send($this->objectsToArray($message->data), $message->senderId);

        if ($message->data instanceof SenderNotificationMessageDTO) {
            $recipientId = $message->data->chatPartner->userId;
            if (!$recipientId) {
                $this->logger->error(
                    sprintf('$recipientId is mandatory. %s', __METHOD__)
                );
            }
            $this->sendCountChats($recipientId);
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

    private function sendCountChats(int $recipientId): void
    {
        $event = new NotificationsSentEvent($recipientId);
        $this->dispatcher->dispatch($event);
    }
} 
