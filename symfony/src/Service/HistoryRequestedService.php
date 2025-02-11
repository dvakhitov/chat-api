<?php

namespace App\Service;

use App\Entity\Chat;
use App\Entity\ChatPartner;
use App\Factory\Notification\NotificationMessageDTOFactory;
use App\Message\NotificationMessage;
use App\Repository\MessageRepository;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;

readonly class HistoryRequestedService
{
    public function __construct(
        private NotificationMessageDTOFactory $notificationMessageDTOFactory,
        private MessageBusInterface $messageBus
    ) {
    }

    /**
     * @throws ExceptionInterface
     */
    public function handle(Chat $chat): void
    {
        foreach ($chat->getChatPartners() as $chatPartner) {
            $notificationMessage = $this
                ->notificationMessageDTOFactory
                ->createHistoryRequestedNotification(
                    $chat,
                    $chatPartner->getUser()->getId()
                );

            $message = new NotificationMessage(
                $notificationMessage,
                $this->getNotificationRecipient($chat, $chatPartner),
            );

            $this->messageBus->dispatch($message);
        }

    }

    private function getNotificationRecipient(Chat $chat, ChatPartner $currentChatPartner): int
    {
        foreach ($chat->getChatPartners() as $chatPartner) {
            if ($chatPartner !== $currentChatPartner) {
                return $chatPartner->getUser()->getId();
            }
        }

        throw new \RuntimeException('Chat partner not found in chat');
    }
}
