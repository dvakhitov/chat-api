<?php

namespace App\DTO\Api\History\Chat;

class ChatHistoryItemDTO
{
    public int $id;

    public int $chatPartnerId;

    public int $senderId;

    /**
     * Может отсутствовать в некоторых сообщениях.
     */
    public ?int $localId = null;

    public string $createdDate;

    public string $updatedDate;

    public string $content;

    public string $status;
}
