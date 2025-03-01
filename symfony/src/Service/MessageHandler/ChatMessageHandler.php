<?php

namespace App\Service\MessageHandler;

use App\DTO\MessageHandlerResultDTO;
use App\DTO\ChatMessageDtoInterface;
use App\Entity\Chat;
use App\Entity\ChatPartner;
use App\Entity\Message;
use App\Entity\User;
use App\Factory\MessageHandlerResultDTOFactory;
use App\Repository\MessageRepository;
use App\Service\BoxgoAuthService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;

readonly class ChatMessageHandler implements MessageHandlerInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private MessageHandlerResultDTOFactory $resultDtoFactory,
        private MessageRepository $messageRepository,
        private RequestStack $requestStack,
        private BoxgoAuthService $boxgoAuthService
    ) {
    }

    public function handle(ChatMessageDtoInterface $messageData): ?MessageHandlerResultDTO
    {
        $chat = $this->getChat($messageData);

        $sender = $this->getSender($chat, $messageData->sender);
        $recipient = $this->getRecipient($chat, $messageData->chatPartnerId);

        if (!$sender instanceof User || !$recipient instanceof User) {
            throw new \RuntimeException(
                sprintf(
                    'User is not valid for sender or recipient. %s, %s',
                    __METHOD__,
                    __LINE__
                )
            );
        }

        $message = $this->messageRepository->findBy([
            'localId' => $messageData->returnUniqId,
            'sender' => $sender,
        ]);
        if ($message) {
            throw new \RuntimeException('Message already exists!');
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

    private function getSender(Chat $chat, int $senderId): ?User
    {
        /** @var ChatPartner $chatPartner */
        foreach ($chat->getChatPartners()->toArray() as $chatPartner) {
            if ($chatPartner->getUser()->getId() === $senderId) {
                return $chatPartner->getUser();
            }
        }

        return null;
    }

    private function getRecipient(Chat $chat, int $recipientId): ?User
    {
        /** @var ChatPartner $chatPartner */
        foreach ($chat->getChatPartners()->toArray() as $chatPartner) {
            if ($chatPartner->getUser()->getId() === $recipientId) {
                return $chatPartner->getUser();
            }
        }

        $user = $this->boxgoAuthService->getNewUser($this->getToken(), $recipientId);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        if ($user instanceof User) {
            return $user;
        }

        return null;
    }

    public function getToken(): ?string
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            return null;
        }

        $authHeader = $request->headers->get('Authorization');
        if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            return null;
        }

        return $matches[1];
    }
}
