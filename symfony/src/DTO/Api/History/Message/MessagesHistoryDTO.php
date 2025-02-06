<?php

namespace App\DTO\Api\History\Message;

use Symfony\Component\Serializer\Annotation\SerializedName;

class MessagesHistoryDTO
{
    /** @var MessagesHistoryItemDTO[] */
    #[SerializedName('content')]
    public array $content;
}
