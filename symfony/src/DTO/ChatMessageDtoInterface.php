<?php

namespace App\DTO;

use Symfony\Component\Serializer\Annotation\DiscriminatorMap;

#[DiscriminatorMap(typeProperty: 'type', mapping: [
    'message' => ChatMessageContentDTO::class,
    'system' => ChatMessageReadDTO::class,
])]
interface ChatMessageDtoInterface
{
    public function getChatPartnerId(): int;
}
