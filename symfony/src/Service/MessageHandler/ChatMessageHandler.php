<?php

namespace App\Service\MessageHandler;

use App\DTO\MessageHandlerResultDTO;
use App\DTO\ChatMessageDtoInterface;
use App\Entity\Chat;
use App\Entity\ChatPartner;
use App\Entity\Message;
use App\Entity\User;
use App\Factory\MessageHandlerResultDTOFactory;
use Doctrine\ORM\EntityManagerInterface;

readonly class ChatMessageHandler implements MessageHandlerInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private MessageHandlerResultDTOFactory $resultDtoFactory,
    ) {
    }

    public function handle(ChatMessageDtoInterface $messageData): ?MessageHandlerResultDTO
    {
        $chat = $this->getChat($messageData);

        $sender = $this->getPartner($chat, $messageData->sender);
        $recipient = $this->getPartner($chat, $messageData->chatPartnerId);

        if (!$sender instanceof User || !$recipient instanceof User) {
            throw new \RuntimeException(sprintf('User is not valid for sender or recipient.', self::class, __METHOD__));
        }

        $message = new Message()
            ->setChat($chat)
            ->setContent($messageData->content)
            ->setSender($sender)
            ->setRecipient($recipient)
            ->setCreatedAt(new \DateTimeImmutable())
            ->setLocalId($messageData->returnUniqId);

        $this->entityManager->persist($message);
        $this->entityManager->flush();

        return $this->resultDtoFactory->create(
            $chat,
            $message
        );
    }

    private function getChat(ChatMessageDtoInterface $messageData)
    {
        $users = $this->entityManager->getRepository(User::class)->findByIds(
            [$messageData->sender, $messageData->chatPartnerId]
        );

        if (count($users) < 2) {
            throw new \RuntimeException('User is not valid!');
        }
        return $this->entityManager->getRepository(Chat::class)->findOrCreatePrivateChat($users[0], $users[1]);
    }

    private function getPartner(Chat $chat, int $userId): ?User
    {
        /** @var ChatPartner $chatPartner */
        foreach ($chat->getChatPartners()->toArray() as $chatPartner) {
            if ($chatPartner->getUser()->getId() === $userId) {
                return $chatPartner->getUser();
            }
        }

        return null;
    }
}
