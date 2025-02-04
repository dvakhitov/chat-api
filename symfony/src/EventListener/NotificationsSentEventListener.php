<?php

namespace App\EventListener;

use App\Event\NotificationsSentEvent;
use App\Repository\MessageRepository;
use App\Service\WebSocketService;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

final readonly class NotificationsSentEventListener
{
    public function __construct(
        private MessageRepository $messageRepository,
        private WebSocketService $webSocketService,
    ) {
    }

    #[AsEventListener(event: NotificationsSentEvent::class)]
    public function onNotificationsSentEventListener(NotificationsSentEvent $event): void
    {
        $data = [
            'type' => 'chat',
            'countChats' => $this->getCountChats($event->getRecipientId())
        ];

        $this->webSocketService->send($data, $event->getRecipientId());
    }

    private function getCountChats(int $recipientId): int
    {
        return $this->messageRepository->countUnreadChatsForRecipient($recipientId);
    }
}
