<?php

namespace App\DTO\Api\History\Chat;

use Symfony\Component\Serializer\Annotation\SerializedName;
class ChatHistoryDTO
{
    /** @var ChatContentDTO[] */
    #[SerializedName('content')]
    public array $content;

    public int $chatPartnerId;
}
