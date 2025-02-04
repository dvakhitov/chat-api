<?php

namespace App\EventListener;

use App\Event\NotificationsSentEvent;
use App\Repository\ChatRepository;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

final class NotificationsSentEventListener
{
    public function __construct(ChatRepository $chatRepository)
    {
    }

    #[AsEventListener(event: NotificationsSentEvent::class)]
    public function onNotificationsSentEventListener(NotificationsSentEvent $event): void
    {
        $message = [
            'type' => 'chat',
            'countChats' => $this->getCountChats($event->getRecipientId())
        ];
    }

    private function getCountChats(int $recipientId): int
    {

    }
}
