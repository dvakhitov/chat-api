<?php

namespace App\DTO\Api\History\Message;

class MessagesHistoryItemDTO
{
    public int $id;

    public int $chatPartnerId;

    public int $senderId;

    public string $createdDate;

    public string $updatedDate;

    public string $content;

    public string $status;
}
