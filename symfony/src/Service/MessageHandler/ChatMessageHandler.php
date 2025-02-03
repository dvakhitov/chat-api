<?php

namespace App\Service\MessageHandler;

use App\DTO\MessageHandlerResultDTO;
use App\DTO\ChatMessageDtoInterface;
use App\DTO\NotificationMessage\AbstractNotificationMessageDTO;
use App\DTO\NotificationMessage\RecipientNotificationMessageDTO;
use App\DTO\NotificationMessage\SenderNotificationMessageDTO;
use App\Entity\Chat;
use App\Entity\ChatPartner;
use App\Entity\Message;
use App\Entity\User;
use App\Factory\ChatPartnerDTOFactory;
use App\Factory\NotificationMessageDTOFactory;
use App\Repository\ChatRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\DTO\ChatPartnerDTO;
use PhpParser\Node\Stmt\Catch_;

class ChatMessageHandler implements MessageHandlerInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ChatRepository $chatRepository,
        private NotificationMessageDTOFactory $notificationMessageDTOFactory
    ) {
    }

    public function handle(ChatMessageDtoInterface $messageData): MessageHandlerResultDTO
    {
        $chat = $this->getChat($messageData);

        $sender = $this->getSender($chat, $messageData->sender);
        if (!$sender instanceof User) {
            throw new \RuntimeException(sprintf('User is not valid for sender.', self::class, __METHOD__));
        }

        $message = new Message()
            ->setChat($chat)
            ->setContent($messageData->content)
            ->setSender($sender)
            ->setCreatedAt(new \DateTimeImmutable())
            ->setLocalId($messageData->returnUniqId);

        $this->entityManager->persist($message);
        $this->entityManager->flush();

        $resultDto = new MessageHandlerResultDTO();
        $resultDto->messageId = $message->getId();
        $resultDto->chatId = $chat->getId();
        $resultDto->notifications = $this->createNotifications($chat, $messageData->sender, $messageData->returnUniqId);

        return $resultDto;
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

    private function createNotifications(Chat $chat, int $messageSenderId, int $returnUniqId): array
    {
        $notifications = [];

        foreach ($chat->getChatPartners() as $chatPartner) {
            if ($chatPartner->getUser()->getId() !== $messageSenderId) {
                $notificationRecipient = $chatPartner;
                /**
                 * Тут создаём уведомление для отправителя, о том, что сообщение отправлено получателю.
                 */
                $notification = $this
                    ->notificationMessageDTOFactory
                    ->createSenderNotification(
                        $chatPartner->getChat(),
                        $notificationRecipient->getUser()->getId(),
                        $returnUniqId
                    );

                $notification->returnUniqId = $returnUniqId;

                $notifications[] = $notification;

                continue;
            }

            /**
             * Здесь создаем уведомление для получателя, что ему пришло сообщение.
             */
            $notifications[] = $this
                ->notificationMessageDTOFactory
                ->createRecipientNotification(
                    $chatPartner->getChat(),
                    $messageSenderId
                );
        }

        return $notifications;
    }
}
