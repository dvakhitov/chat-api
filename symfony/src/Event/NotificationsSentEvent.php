<?php

namespace App\Event;

use Symfony\Contracts\EventDispatcher\Event;
class NotificationsSentEvent extends Event
{
    private int $recipientId;

    public function __construct(int $recipientId)
    {
        $this->recipientId = $recipientId;
    }
    public function getRecipientId(): int
    {
        return $this->recipientId;
    }
}
