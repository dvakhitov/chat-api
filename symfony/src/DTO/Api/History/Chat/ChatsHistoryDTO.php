<?php

namespace App\DTO\Api\History\Chat;

use Symfony\Component\Serializer\Annotation\SerializedName;

class ChatsHistoryDTO
{
    /** @var ChatHistoryItemDTO[] */
    #[SerializedName('content')]
    public array $content;
}
