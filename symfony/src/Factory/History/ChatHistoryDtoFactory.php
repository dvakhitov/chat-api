<?php

namespace App\Factory\History;

use App\DTO\Api\History\Chat\ChatContentDTO;
use App\DTO\Api\History\Chat\ChatHistoryDTO;
use App\DTO\Api\History\Chat\ChatPartnerDTO;
use App\DTO\Api\History\Chat\LastMessageDTO;
use App\Entity\Chat;
use App\Helper\DateTimeHelper;

class ChatHistoryDtoFactory
{
    /**
     * @param Chat[] $chats
     * @return ChatHistoryDTO
     */
    public function create(array $chats): ChatHistoryDTO
    {
        // 2. Создаём корневой DTO
        $chatDTO = new ChatHistoryDTO();
        $chatDTO->content = [];

        // 3. Наполняем DTO данными
        foreach ($chats as $chat) {
            $message = $chat->getLastMessage();
            $chatContentDTO = new ChatContentDTO();

            // ---- chatPartner (на выбор sender или recipient) ----
            $messageRecipient = $message->getRecipient();
            $chatPartnerDTO = new ChatPartnerDTO();
            $chatPartnerDTO->id = $messageRecipient->getId();
            $chatPartnerDTO->email = $messageRecipient->getEmail();
            $chatPartnerDTO->firstName = $messageRecipient->getFirstName();
            $chatPartnerDTO->lastName = $messageRecipient->getLastName();
            $chatPartnerDTO->photoUrl = $messageRecipient->getPhotoUrl();
            if (!$messageRecipient->getPhotoUrl()) {
                unset($chatPartnerDTO->photoUrl);
            }
            $chatPartnerDTO->createdDate = ($messageRecipient->getCreatedAt()) ? DateTimeHelper::formatWithTimezone($messageRecipient->getCreatedAt()) : '';
            $chatPartnerDTO->emailVerified = (bool)$messageRecipient->getIsEmailVerified();

            $chatContentDTO->chatPartner = $chatPartnerDTO;

            // ---- lastMessage ----
            $lastMessageDTO = new LastMessageDTO();
            $lastMessageDTO->id = $message->getId();
            $lastMessageDTO->senderId = $message->getSender()?->getId() ?? 0;
            $lastMessageDTO->createdDate = $message->getCreatedAt()? DateTimeHelper::formatWithTimezone($message->getCreatedAt()) : '';
            $lastMessageDTO->updatedDate = $message->getUpdatedAt()? DateTimeHelper::formatWithTimezone($message->getUpdatedAt()) : '';
            $lastMessageDTO->content = $message->getContent() ?? '';
            $lastMessageDTO->status = $message->getStatus();

            $chatContentDTO->lastMessage = $lastMessageDTO;

            // ---- кол-во непрочитанных (примерная логика) ----
            $chatContentDTO->numberUnreadMessages = $message->isRead() ? 0 : 1;

            // ---- некий timestamp (пример) ----
            $chatContentDTO->numberUnreadTimeStamp = time() * 1000;

            $chatDTO->content[] = $chatContentDTO;
        }

        return $chatDTO;
    }
}
