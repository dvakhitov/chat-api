<?php

namespace App\Service;

use App\Entity\Chat;
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
                    $chat->getLastMessage()->getSender()->getId()
                );

            $message = new NotificationMessage(
                $notificationMessage,
                $chatPartner->getUser()->getId(),
            );

            $this->messageBus->dispatch($message);
        }

    }
}
