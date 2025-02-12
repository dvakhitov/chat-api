<?php

namespace App\Event;

use App\Entity\Chat;
use Symfony\Contracts\EventDispatcher\Event;

class HistoryRequestedEvent extends Event
{
    public function __construct(
        private readonly Chat $chat,
        private readonly int $notificationRecipientId,
    ) {
    }

    public function getChat(): Chat
    {
        return $this->chat;
    }

    public function getNotificationRecipientId(): int
    {
        return $this->notificationRecipientId;
    }
}
