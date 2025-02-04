<?php

namespace App\Event;

class NotificationSentEvent
{
    private string $type;

    private int $countChats;

    private int $recipientId;

    public function __construct(string $countChats, string $type = 'chat')
    {
        $this->type = $type;
        $this->countChats = $countChats;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function getCountChats(): int
    {
        return $this->countChats;
    }

    public function setCountChats(int $countChats): void
    {
        $this->countChats = $countChats;
    }
}
