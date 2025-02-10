<?php

namespace App\Factory\Notification;

use App\DTO\ChatPartnerDTO;
use App\DTO\NotificationMessage\AbstractNotificationMessageDTO;
use App\DTO\NotificationMessage\HistoryRequestedNotificationMessageDTO;
use App\DTO\NotificationMessage\MessageRecipientNotificationMessageDTO;
use App\DTO\NotificationMessage\MessageSenderNotificationMessageDTO;
use App\Entity\Chat;
use App\Entity\Message;
use App\Factory\ChatPartnerDTOFactory;
use App\Factory\LastMessageDtoFactory;
use App\Repository\ChatRepository;
use App\Repository\MessageRepository;

readonly class NotificationMessageDTOFactory
{
    public function __construct(
        private ChatRepository $chatRepository,
        private MessageRepository $messageRepository,
    ) {
    }

    public function createSenderNotification(
        Chat $chat,
        int $messageSenderId,
        int $localId
    ): MessageSenderNotificationMessageDTO {
        /** @var MessageSenderNotificationMessageDTO $dto */
        $dto = $this->createNotification(MessageSenderNotificationMessageDTO::class, $chat, $messageSenderId, $localId);
        $dto->lastMessage->localId = $localId;

        return $dto;
    }

    public function createHistoryRequestedNotification(
        Chat $chat,
        int $messageSenderId,
    ): AbstractNotificationMessageDTO {
        return $this->createNotification(
            HistoryRequestedNotificationMessageDTO::class,
            $chat,
            $messageSenderId
        );
    }

    public function createRecipientNotification(
        Chat $chat,
        int $messageRecipient
    ): MessageRecipientNotificationMessageDTO|AbstractNotificationMessageDTO {
        $dto = $this->createNotification(
            MessageRecipientNotificationMessageDTO::class,
            $chat,
            $messageRecipient
        );

        unset($dto->lastMessage->localId);

        return $dto;
    }

    private function createNotification(
        string $className,
        Chat $chat,
        int $chatPartner,
        ?int $returnUniqId = null
    ): AbstractNotificationMessageDTO {
        /** @var AbstractNotificationMessageDTO $dto */
        $dto = new $className($chat, $chatPartner);
        $dto->chatPartner = $this->getChatPartner($chat, $chatPartner);
        $dto->lastMessage = LastMessageDtoFactory::create($chat, is_null($returnUniqId));
        $dto->numberUnreadTimeStamp = $this->getNumberUnreadTimeStamp($chat, $chatPartner);
        $dto->numberUnreadMessages = $this->getNumberUnreadMessages($chat, $dto->chatPartner);

        return $dto;
    }

    private function getChatPartner(Chat $chat, int $messageSenderId)
    {
        foreach ($chat->getChatPartners() as $partner) {
            if ($partner->getUser()->getId() === $messageSenderId) {
                return ChatPartnerDTOFactory::create($partner);
            }
        }
    }

    private function getNumberUnreadMessages(Chat $chat, ChatPartnerDTO $chatPartnerDTO): int
    {
        $actualChatPartner = null;

        foreach ($chat->getChatPartners() as $chatPartner) {
            if ($chatPartner->getUser()->getId() !== $chatPartnerDTO->userId) {
                $actualChatPartner = $chatPartner;
            }
        }

        return $this->chatRepository->getCountUnreadMessagesByChatPartner($actualChatPartner->getId(), $chat->getId());
    }

    private function getNumberUnreadTimeStamp(Chat $chat, int $partnerId): int
    {
        $message = $this->messageRepository->getLastUnreadMessage($chat, $partnerId);

        if ($message instanceof Message) {
            return $message->getCreatedAt()->getTimestamp();
        }

        return 0;
    }
}
