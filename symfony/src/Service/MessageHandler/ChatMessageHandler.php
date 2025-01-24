<?php

namespace App\Service\MessageHandler;

use App\DTO\MessageHandlerResultDTO;
use App\DTO\NotificationMessage\AbstractNotificationMessageDTO;
use App\DTO\NotificationMessage\RecipientNotificationMessageDTO;
use App\DTO\NotificationMessage\SenderNotificationMessageDTO;
use App\DTO\ProcessMessageDTO;
use App\Entity\Chat;
use App\Entity\ChatPartner;
use App\Entity\Message;
use App\Entity\User;
use App\Repository\ChatRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\DTO\ChatPartnerDTO;

class ChatMessageHandler implements MessageHandlerInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ChatRepository $chatRepository,
    ) {
    }

    public function handle(ProcessMessageDTO $messageData): MessageHandlerResultDTO
    {
        $chat = $this->getChat($messageData);

        $sender = $this->getSender($chat, $messageData->sender);
        if (!$sender instanceof User) {
            throw new \RuntimeException('User is not valid');
        }

        $message = new Message()
            ->setChat($chat)
            ->setContent($messageData->content)
            ->setSender($sender)
            ->setCreatedAt(new \DateTimeImmutable())
            ->setReturnUniqId($messageData->returnUniqId);

        $this->entityManager->persist($message);
        $this->entityManager->flush();

        $resultDto = new MessageHandlerResultDTO();
        $resultDto->notifications = $this->createNotifications($chat, $messageData->sender, $messageData->returnUniqId);

        return $resultDto;
    }

    private function getChat(ProcessMessageDTO $messageData)
    {
        $users = $this->entityManager->getRepository(User::class)->findByIds(
            [$messageData->sender, $messageData->chatPartnerId]
        );

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

    private function createNotifications(Chat $chat, int $senderId, int $returnUniqId): array
    {
        $notifications = [];

        foreach ($chat->getChatPartners() as $chatPartner) {
            if ($chatPartner->getUser()->getId() === $senderId) {
                $notification = new SenderNotificationMessageDTO();
                $notification->returnUniqId = $returnUniqId;
                // Найдем партнера чата для отправителя
                foreach ($chat->getChatPartners() as $partner) {
                    if ($partner->getUser()->getId() !== $senderId) {
                        $notification->chatPartner = $this->createChatPartnerDTO($partner);
                        break;
                    }
                }

                $notifications[] = $this->fillNotification($notification, $chatPartner);
                continue;
            }

            $notification = new RecipientNotificationMessageDTO();
            // Найдем отправителя для получателя
            foreach ($chat->getChatPartners() as $partner) {
                if ($partner->getUser()->getId() === $senderId) {
                    $notification->chatPartner = $this->createChatPartnerDTO($partner);
                    break;
                }
            }
            $notifications[] = $this->fillNotification($notification, $chatPartner);
        }

        return $notifications;
    }

    private function fillNotification(
        AbstractNotificationMessageDTO $notification,
        ChatPartner $chatPartner
    ): AbstractNotificationMessageDTO {
        $notification->type = 'message';
        $notification->chatPartner = $this->createChatPartnerDTO($chatPartner);
        $notification->numberUnreadMessages = $this->chatRepository->getUnreadMessagesChats($chatPartner);

        return $notification;
    }

    private function createChatPartnerDTO(ChatPartner $chatPartner): ChatPartnerDTO
    {
        $dto = new ChatPartnerDTO();
        $dto->id = $chatPartner->getId();
        $dto->userId = $chatPartner->getUser()->getId();
        $dto->userEmail = $chatPartner->getUser()->getEmail();
        $dto->userFirstName = $chatPartner->getUser()->getFirstName();
        $dto->userLastName = $chatPartner->getUser()->getLastName();
        $dto->userPhotoUrl = $chatPartner->getUser()->getPhotoUrl();

        return $dto;
    }
}
