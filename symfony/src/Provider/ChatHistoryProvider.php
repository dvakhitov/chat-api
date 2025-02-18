<?php

namespace App\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\DTO\Api\History\Chat\ChatHistoryDTO;
use App\Entity\Chat;
use App\Entity\User;
use App\Factory\History\ChatHistoryDtoFactory;
use App\Repository\ChatRepository;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;

readonly class ChatHistoryProvider implements ProviderInterface
{
    public function __construct(
        private ChatRepository $chatRepository,
        private Security $security,
        private ChatHistoryDtoFactory $chatHistoryDtoFactory,
        private LoggerInterface $logger
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ChatHistoryDTO
    {
        try {
            /** @var User $user */
            $user = $this->security->getUser();
            /** @var Chat[] $chats */
            $chats = $this->chatRepository->findAllChatsByUser($user);

            return $this->chatHistoryDtoFactory->create($chats, $user);
        } catch (\Throwable $exception) {
            $this->logger->error($exception->getMessage());
            throw new $exception;
        }

        throw new \Exception('Runtime error. Please contact support.');
    }
}
