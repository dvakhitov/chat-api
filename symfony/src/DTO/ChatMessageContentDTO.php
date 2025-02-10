<?php

namespace App\DTO;

class ChatMessageContentDTO implements ChatMessageDtoInterface
{
    use ChatPartnerId;
    use MessageContentTrait;

    public string $type = 'message' {
        get => $this->type;
    }
}
