<?php

namespace App\DTO;

class ChatMessageReadDTO implements ChatMessageDtoInterface
{
    use ChatPartnerId;
    use MessageContentTrait;

    public ?int $id = null;

    public string $type = 'system';
}