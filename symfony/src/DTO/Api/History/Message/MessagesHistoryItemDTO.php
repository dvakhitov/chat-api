<?php

namespace App\DTO\Api\History\Message;

use Symfony\Component\Serializer\Annotation\SerializedName;
class MessagesHistoryItemDTO
{

    /**
     * "id": 31,
     * "chatPartnerId": 40,
     * "senderId": 52,
     * "createdDate": "2025-02-06T15:30:24+00:00",
     * "updatedDate": "2025-02-06T15:30:24+00:00",
     * "content": "Hello, my friend",
     * "status": "sent"
     */

    public int $id;

    public int $chatPartnerId;

    public int $senderId;

    public string $createdDate;

    public string $updatedDate;

    public string $content;

    public string $status;
}
