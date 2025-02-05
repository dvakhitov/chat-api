<?php

namespace App\DTO\Api\History\Message;

use Symfony\Component\Serializer\Annotation\SerializedName;
class ChatDTO
{
    /** @var ChatContentDTO[] */
    #[SerializedName('content')]
    public array $content;

    public int $chatPartnerId;
}
