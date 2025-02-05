<?php

namespace App\DTO\Api\History\Message;

class LastMessageDTO
{
    public int $id;

    public int $senderId;

    public string $createdDate;

    public string $updatedDate;

    public string $content;

    public string $status;
}
