<?php

namespace App\Factory\History;

use App\DTO\Api\History\Chat\ChatContentDTO;
use App\DTO\Api\History\Chat\ChatHistoryDTO;
use App\DTO\Api\History\Chat\ChatPartnerDTO;
use App\DTO\Api\History\Chat\LastMessageDTO;
use App\Entity\Chat;
use App\Entity\User;
use App\Helper\DateTimeHelper;
use App\Repository\MessageRepository;

class ChatHistoryDtoFactory
{
    public function __construct(private readonly MessageRepository $messageRepository)
    {
    }

    /**
     * @param Chat[] $chats
     * @return ChatHistoryDTO
     */
    public function create(array $chats, User $user): ChatHistoryDTO
    {
        // 2. Создаём корневой DTO
        $chatDTO = new ChatHistoryDTO();
        $chatDTO->content = [];

        // 3. Наполняем DTO данными
        foreach ($chats as $chat) {
            $message = $chat->getLastMessage();
            $chatContentDTO = new ChatContentDTO();

            // ---- chatPartner (на выбор sender или recipient) ----
            $chatPartner = $this->getChatPartner($chat, $user);
            $chatPartnerDTO = new ChatPartnerDTO();
            $chatPartnerDTO->id = $chatPartner->getId();
            $chatPartnerDTO->email = $chatPartner->getEmail();
            $chatPartnerDTO->firstName = $chatPartner->getFirstName();
            $chatPartnerDTO->lastName = $chatPartner->getLastName();
//            $chatPartnerDTO->photoUrl = $messageRecipient->getPhotoUrl();
//            if (!$messageRecipient->getPhotoUrl()) {
//                unset($chatPartnerDTO->photoUrl);
//            }
            unset($chatPartnerDTO->photoUrl);
            $chatPartnerDTO->createdDate = ($chatPartner->getCreatedAt()) ? DateTimeHelper::formatWithTimezone(
                $chatPartner->getCreatedAt()
            ) : '';
            $chatPartnerDTO->emailVerified = (bool)$chatPartner->getIsEmailVerified();

            $chatContentDTO->chatPartner = $chatPartnerDTO;

            // ---- lastMessage ----
            $lastMessageDTO = new LastMessageDTO();
            $lastMessageDTO->id = $message->getId();
            $lastMessageDTO->senderId = $message->getSender()?->getId() ?? 0;
            $lastMessageDTO->createdDate = $message->getCreatedAt() ? DateTimeHelper::formatWithTimezone(
                $message->getCreatedAt()
            ) : '';
            $lastMessageDTO->updatedDate = $message->getUpdatedAt() ? DateTimeHelper::formatWithTimezone(
                $message->getUpdatedAt()
            ) : '';
            $lastMessageDTO->content = $message->getContent() ?? '';
            $lastMessageDTO->status = $message->getStatus();

            $chatContentDTO->lastMessage = $lastMessageDTO;

            // ---- кол-во непрочитанных (примерная логика) ----
            $chatContentDTO->numberUnreadMessages = $this->messageRepository->countUnreadMessagesOfTheChatFoUser(
                $chat,
                $user
            );

            // ---- некий timestamp (пример) ----
            $chatContentDTO->numberUnreadTimeStamp = (int)$this
                ->messageRepository
                ->getLastUnreadMessage(
                    $chat,
                    $this->getChatPartner($chat, $user)->getId()
                )?->getCreatedAt()->getTimestamp();

            $chatDTO->content[] = $chatContentDTO;
        }

        return $chatDTO;
    }

    private function getChatPartner(Chat $chat, User $user): User
    {
        foreach ($chat->getChatPartners() as $partner) {
            if ($partner->getUser()->getId() !== $user->getId()) {
                return $partner->getUser();
            }
        }

        throw new \RuntimeException('Chat partner not found');
    }
}
