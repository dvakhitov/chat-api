<?php

namespace App\Message;

readonly class ChatMessage
{
    public function __construct(
        private string $type,
        private string $sender,
        private ?string $recipient,
        private string $content,
        private string $timestamp
    ) {}

    public function getType(): string
    {
        return $this->type;
    }

    public function getSender(): string
    {
        return $this->sender;
    }

    public function getRecipient(): ?string
    {
        return $this->recipient;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function getTimestamp(): string
    {
        return $this->timestamp;
    }
} 