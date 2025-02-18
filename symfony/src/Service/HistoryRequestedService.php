<?php

namespace App\Service;

use App\Entity\Chat;
use App\Entity\ChatPartner;
use App\Factory\Notification\NotificationMessageDTOFactory;
use App\Message\NotificationMessage;
use App\Repository\MessageRepository;
use Doctrine\DBAL\Exception\DeadlockException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;

readonly class HistoryRequestedService
{
    public function __construct(
        private NotificationMessageDTOFactory $notificationMessageDTOFactory,
        private MessageBusInterface $messageBus,
        private CountUnreadChatsService $countUnreadChatsService,
        private WebSocketService $webSocketService,
        private LoggerInterface $logger,
        private MessageRepository $messageRepository,
    ) {
    }

    /**
     * @throws ExceptionInterface
     * @throws DeadlockException
     */
    public function handle(Chat $chat, int $notificationRecipientId): void
    {
        $this->messageRepository->setIsReadByUser($notificationRecipientId, $chat->getId());
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

        try {
            $systemMessageData = [
                'type' => 'chat',
                'countChats' => $this->countUnreadChatsService
                    ->countUsersUnreadChats($notificationRecipientId)
            ];

            $this->webSocketService->send(
                $systemMessageData,
                $chat->getChatPartnerByUserId($notificationRecipientId)->getUser()->getId()
            );
        } catch (\Exception $e) {
            $this->logger->error('Error sending data to WebSocket server', [
                'exception' => $e,
            ]);
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
