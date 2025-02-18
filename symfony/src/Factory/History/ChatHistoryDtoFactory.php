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
use Psr\Log\LoggerInterface;

class ChatHistoryDtoFactory
{
    public function __construct(
        private readonly MessageRepository $messageRepository
    ) {
    }

    /**
     * Creates a ChatHistoryDTO instance based on the provided chats and user.
     *
     * @param array $chats An array of chat entities.
     * @param User $user The user for whom the chat history is being created.
     *
     * @return ChatHistoryDTO The created chat history DTO containing chat data.
     */
    public function create(array $chats, User $user): ChatHistoryDTO
    {
        // 2. Создаём корневой DTO
        $chatDTO = new ChatHistoryDTO();
        $chatDTO->content = [];

        foreach ($chats as $chat) {
            if ($chat->getChatPartners()->count() < 2) {
                continue;
            }
            $message = $chat->getLastMessage();
            if (!$message) {
                $message = $this->messageRepository->getLastMessageOfChat($chat);
                if (!$message) {
                    continue;
                }
            }
            $chatContentDTO = new ChatContentDTO();

            // ---- chatPartner (на выбор sender или recipient) ----
            $chatPartner = $this->getChatPartner($chat, $user);

            $chatPartnerDTO = new ChatPartnerDTO();
            $chatPartnerDTO->id = $chatPartner?->getId();
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
            if (!$message) {
                throw new \RuntimeException(sprintf('Message not found: %s, %s', __METHOD__, __LINE__));
            }
            $lastMessageDTO->id = $message->getId();
            $lastMessageDTO->senderId = $message?->getSender()?->getId() ?? 0;
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
                    $chatPartner?->getId()
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
