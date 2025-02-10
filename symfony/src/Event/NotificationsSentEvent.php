<?php

namespace App\Event;

use Symfony\Contracts\EventDispatcher\Event;
class NotificationsSentEvent extends Event
{
    private int $recipientId;

    private bool $isSystem;

    private int $messageId;

    public function __construct(
        int $recipientId,
        int $messageId,
        bool $isSystem = false)
    {
        $this->recipientId = $recipientId;
        $this->messageId = $messageId;
        $this->isSystem = $isSystem;
    }

    public function getRecipientId(): int
    {
        return $this->recipientId;
    }

    public function isSystem(): bool
    {
        return $this->isSystem;
    }

    public function getMessageId(): int
    {
        return $this->messageId;
    }
}
