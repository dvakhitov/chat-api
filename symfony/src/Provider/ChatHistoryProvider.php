<?php

namespace App\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\DTO\Api\History\Chat\ChatHistoryItemDTO;
use App\DTO\Api\History\Chat\ChatsHistoryDTO;
use App\Entity\Chat;
use App\Entity\User;
use App\Repository\ChatRepository;
use Symfony\Bundle\SecurityBundle\Security;

readonly class ChatHistoryProvider implements ProviderInterface
{
    public function __construct(
        private ChatRepository $chatRepository,
        private Security $security,
    ) {
    }

    /**
     * Метод, который вызывается Api Platform
     * при запросе GET /chat/message/history
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ChatsHistoryDTO
    {
        /** @var User $user */
        $user = $this->security->getUser();
        /** @var Chat[] $chats */
        $chats = $this->chatRepository->findAllChatsByUser($user);


        // 2. Создаём корневой DTO
        $chatDTO = new ChatsHistoryDTO();

        // 3. Наполняем DTO данными
        foreach ($chats as $chat) {
            // ---- lastMessage ----
            $itemDTO = new ChatHistoryItemDTO();
            $message = $chat[0]->getLastMessage();
            $itemDTO->id = $message->getId();
            $itemDTO->chatPartnerId = $message->getRecipient()->getId();
            $itemDTO->content = $message->getContent();
            $itemDTO->senderId = $message->getSender()->getId();
            $itemDTO->createdDate = $message->getCreatedAt()?->format(DATE_ATOM) ?? '';
            $itemDTO->updatedDate = $message->getUpdatedAt()?->format(DATE_ATOM) ?? '';
            $itemDTO->status = $message->getStatus();
            if ($user === $message->getSender()) {
                $itemDTO->localId = $message->getLocalId();
            }

            $chatDTO->content[] = $itemDTO;
        }

        return $chatDTO;
    }
}
