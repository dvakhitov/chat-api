<?php

namespace App\Factory;

use App\DTO\NotificationMessage\LastMessageDto;
use App\Entity\Chat;
use App\Repository\MessageRepository;

class LastMessageDtoFactory
{
    public function __construct(private MessageRepository $messageRepository)
    {
    }

    public function create(Chat $chat, int $userId): LastMessageDto
    {
        $message = $chat->getLastMessage();

        $lastMessageDto = new LastMessageDto();
        $lastMessageDto->id = $message->getId();
        $lastMessageDto->localId = $message->getLocalId();
        if ($message->getSender()->getId() === $userId) {
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
