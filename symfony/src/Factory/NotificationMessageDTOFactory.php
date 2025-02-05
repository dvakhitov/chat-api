<?php

namespace App\Factory;

use App\DTO\ChatPartnerDTO;
use App\DTO\NotificationMessage\AbstractNotificationMessageDTO;
use App\DTO\NotificationMessage\HistoryRequestedNotificationMessageDTO;
use App\DTO\NotificationMessage\RecipientNotificationMessageDTO;
use App\DTO\NotificationMessage\SenderNotificationMessageDTO;
use App\Entity\Chat;
use App\Repository\ChatRepository;

readonly class NotificationMessageDTOFactory
{
    public function __construct(private ChatRepository $chatRepository)
    {
    }

    public function createSenderNotification(
        Chat $chat,
        int $messageSenderId
    ): SenderNotificationMessageDTO {
        /** @var SenderNotificationMessageDTO $dto */
        $dto = $this->createNotification(SenderNotificationMessageDTO::class, $chat, $messageSenderId);

        return $dto;
    }

    public function createHistoryRequestedNotification(
        Chat $chat,
        int $messageSenderId,
    ) {
        return $this->createNotification(
            HistoryRequestedNotificationMessageDTO::class,
            $chat,
            $messageSenderId
        );
    }

    public function createRecipientNotification(
        Chat $chat,
        int $messageSenderId,
        int $returnUniqId
    ): RecipientNotificationMessageDTO|AbstractNotificationMessageDTO {
        return $this->createNotification(
            RecipientNotificationMessageDTO::class,
            $chat,
            $messageSenderId,
            $returnUniqId
        );
    }

    private function createNotification(
        string $className,
        Chat $chat,
        int $messageSenderId,
        ?int $returnUniqId = null
    ): AbstractNotificationMessageDTO {
        /** @var AbstractNotificationMessageDTO $dto */
        $dto = new $className($chat, $messageSenderId);
        $dto->chatPartner = $this->getChatPartner($chat, $messageSenderId);
        $dto->lastMessage = LastMessageDtoFactory::create($chat, is_null($returnUniqId));
        $dto->numberUnreadTimeStamp = $chat->getLastMessage()->getUpdatedAt()->getTimestamp();
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
}
