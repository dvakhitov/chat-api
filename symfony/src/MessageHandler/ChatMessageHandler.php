<?php

namespace App\MessageHandler;

use App\Message\ChatMessage;
use App\Entity\Message;
use App\Entity\Chat;
use App\Repository\UserRepository;
use App\Repository\ChatRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class ChatMessageHandler
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserRepository $userRepository,
        private ChatRepository $chatRepository
    ) {}

    public function __invoke(ChatMessage $message): void
    {
        $sender = $this->userRepository->findOneByChatUuid($message->getSender());

        if (!$sender) {
            throw new \Exception('Sender not found');
        }

        $chat = null;
        if ($message->getType() === 'private') {
            $recipient = $this->userRepository->findOneByChatUuid($message->getRecipient());
            $chat = $this->chatRepository->findOrCreatePrivateChat($sender, $recipient);
        } else {
            // Handle group chat logic
        }

        $messageEntity = new Message();
        $messageEntity->setSender($sender);
        $messageEntity->setChat($chat);
        $messageEntity->setContent($message->getContent());
        
        $this->entityManager->persist($messageEntity);
        $this->entityManager->flush();
    }
} 
