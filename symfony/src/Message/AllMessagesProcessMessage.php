<?php

namespace App\Message;

readonly class AllMessagesProcessMessage
{
    public function __construct(
        private array $requestData,
        private int $chatMessageSenderId
    ) {
    }

    public function getRequestData(): array
    {
        return $this->requestData;
    }

    public function getChatMessageSenderId(): int
    {
        return $this->chatMessageSenderId;
    }
}