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

    public function create(Message $message): MessageHandlerResultDTO
    {
        $resultDto = new MessageHandlerResultDTO();
        $resultDto->messageId = $message->getId();
        $resultDto->chatId = $message->getChat()->getId();

        $resultDto->notifications = $this->createNotifications(
            $message,
            $message->getLocalId()
        );

        return $resultDto;
    }

    private function createNotifications(Message $message, int $localId): array
    {
        $notifications = [];
        $notifications['sender'] = $this
            ->notificationMessageDTOFactory
            ->createSenderNotification(
                $message->getChat(),
                $message->getRecipient()->getId(),
                $localId
            );

        $notifications['recipient'] = $this
            ->notificationMessageDTOFactory
            ->createRecipientNotification(
                $message->getChat(),
                $message->getSender()->getId(),
            );

        return $notifications;
    }
}