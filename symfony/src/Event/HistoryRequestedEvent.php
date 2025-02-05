<?php

namespace App\Event;

use App\Entity\Chat;
use App\Entity\User;
use Symfony\Contracts\EventDispatcher\Event;

class HistoryRequestedEvent extends Event
{
    public function __construct(
        private Chat $chat,
        private User $recipient
    ) {
    }

    public function getChat(): Chat
    {
        return $this->chat;
    }

    public function getRecipient(): User
    {
        return $this->recipient;
    }
}
