<?php

namespace App\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\DTO\Api\History\Chat\ChatHistoryDTO;
use App\Entity\Chat;
use App\Entity\User;
use App\Factory\History\ChatHistoryDtoFactory;
use App\Repository\ChatRepository;
use Symfony\Bundle\SecurityBundle\Security;

readonly class ChatHistoryProvider implements ProviderInterface
{
    public function __construct(
        private ChatRepository $chatRepository,
        private Security $security,
        private ChatHistoryDtoFactory $chatHistoryDtoFactory
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ChatHistoryDTO
    {
        /** @var User $user */
        $user = $this->security->getUser();
        /** @var Chat[] $chats */
        $chats = $this->chatRepository->findAllChatsByUser($user);

        return $this->chatHistoryDtoFactory->create($chats, $user);
    }
}
