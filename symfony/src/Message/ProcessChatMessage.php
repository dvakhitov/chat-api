<?php

namespace App\Message;

use App\DTO\ChatMessageDtoInterface;

class ProcessChatMessage
{
    public function __construct(
        private ChatMessageDtoInterface $messageData
    ) {
    }

    public function getMessageData(): ChatMessageDtoInterface
    {
        return $this->messageData;
    }
}
