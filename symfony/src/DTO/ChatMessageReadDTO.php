<?php

namespace App\DTO;

class ChatMessageReadDTO implements ChatMessageDtoInterface
{
    use ChatPartnerId;

    public ?int $id = null;

    public string $type;

    public ?int $sender = null;
}