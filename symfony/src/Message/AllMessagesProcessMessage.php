<?php

namespace App\Message;

readonly class AllMessagesProcessMessage
{
    public function __construct(
        private array $requestData,
        private int $userId
    ) {
    }

    public function getRequestData(): array
    {
        return $this->requestData;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }
}