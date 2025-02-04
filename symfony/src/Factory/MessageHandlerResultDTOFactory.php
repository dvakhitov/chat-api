<?php

namespace App\Factory;

use App\DTO\MessageHandlerResultDTO;
use App\Entity\Chat;
use App\Entity\Message;

readonly class MessageHandlerResultDTOFactory
{
    public function __construct(
        private NotificationMessageDTOFactory $notificationMessageDTOFactory
    ) {
    }

    public function create($messageData, $chat, Message $message): MessageHandlerResultDTO
    {
        $resultDto = new MessageHandlerResultDTO();
        $resultDto->messageId = $message->getId();
        $resultDto->chatId = $chat->getId();

        $resultDto->notifications = $this->createNotifications($chat, $message->getSender()->getId(), $message->getLocalId());

        return $resultDto;
    }

    private function createNotifications(Chat $chat, int $messageSenderId, int $returnUniqId): array
    {
        $notifications = [];

        foreach ($chat->getChatPartners() as $chatPartner) {
            if ($chatPartner->getUser()->getId() !== $messageSenderId) {
                $notificationRecipient = $chatPartner;
                /**
                 * Тут создаём уведомление для отправителя, о том, что сообщение отправлено получателю.
                 */
                $notification = $this
                    ->notificationMessageDTOFactory
                    ->createSenderNotification(
                        $chatPartner->getChat(),
                        $notificationRecipient->getUser()->getId()
                    );

                $notifications[] = $notification;

                continue;
            }

            /**
             * Здесь создаем уведомление для получателя, что ему пришло сообщение.
             */
            $notifications[] = $this
                ->notificationMessageDTOFactory
                ->createRecipientNotification(
                    $chatPartner->getChat(),
                    $messageSenderId,
                    $returnUniqId
                );
        }

        return $notifications;
    }
}