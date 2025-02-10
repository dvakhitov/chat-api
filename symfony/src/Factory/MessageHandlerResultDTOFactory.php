<?php

namespace App\Factory;

use App\DTO\MessageHandlerResultDTO;
use App\Entity\Chat;
use App\Entity\Message;
use App\Factory\Notification\NotificationMessageDTOFactory;

readonly class MessageHandlerResultDTOFactory
{
    public function __construct(
        private NotificationMessageDTOFactory $notificationMessageDTOFactory
    ) {
    }

    public function create($chat, Message $message): MessageHandlerResultDTO
    {
        $resultDto = new MessageHandlerResultDTO();
        $resultDto->messageId = $message->getId();
        $resultDto->chatId = $chat->getId();

        $resultDto->notifications = $this->createNotifications(
            $chat,
            $message,
            $message->getLocalId()
        );

        return $resultDto;
    }

    private function createNotifications(Chat $chat, Message $message, int $localId): array
    {
        $notifications = [];
        $notifications['sender'] = $this
            ->notificationMessageDTOFactory
            ->createSenderNotification(
                $chat,
                $message->getRecipient()->getId(),
                $localId
            );

        $notifications['recipient'] = $this
            ->notificationMessageDTOFactory
            ->createRecipientNotification(
                $chat,
                $message->getSender()->getId(),
            );

        return $notifications;
    }
}