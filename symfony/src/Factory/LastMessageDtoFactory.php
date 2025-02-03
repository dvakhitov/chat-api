<?php

namespace App\Factory;

use App\DTO\NotificationMessage\LastMessageDto;
use App\Entity\Chat;

class LastMessageDtoFactory
{
    public static function create(Chat $chat, bool $withLocalId): LastMessageDto
    {
        $message = $chat->getLastMessage();

        $lastMessageDto = new LastMessageDto();
        $lastMessageDto->id = $message->getId();
        $lastMessageDto->localId = $message->getLocalId();
        if (!$withLocalId) {
            unset($lastMessageDto->localId);
        }
        $lastMessageDto->senderId = $message->getSender()->getId();
        $lastMessageDto->createdDate = $message->getCreatedAt();
        $lastMessageDto->updatedDate = $message->getUpdatedAt();
        $lastMessageDto->content = $message->getContent();
        $lastMessageDto->status = $message->getStatus();

        return $lastMessageDto;
    }
}
