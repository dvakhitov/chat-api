<?php

namespace App\Message;

use App\DTO\ProcessMessageDTO;

class ProcessChatMessage
{
    public function __construct(
        private ProcessMessageDTO $messageData
    ) {}

    public function getMessageData(): ProcessMessageDTO
    {
        return $this->messageData;
    }
}
