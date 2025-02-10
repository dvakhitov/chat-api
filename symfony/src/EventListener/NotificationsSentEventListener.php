<?php

namespace App\EventListener;

use App\Event\NotificationsSentEvent;
use App\Repository\MessageRepository;
use App\Service\WebSocketService;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

final readonly class NotificationsSentEventListener
{
    public function __construct(
        private MessageRepository $messageRepository,
        private WebSocketService $webSocketService,
        private LoggerInterface $logger,
    ) {
    }

    #[AsEventListener(event: NotificationsSentEvent::class)]
    public function onNotificationsSentEventListener(NotificationsSentEvent $event): void
    {
        $data = [
            'type' => 'chat',
            'countChats' => $this->getCountChats($event->getRecipientId())
        ];


        try {
            if ($event->isSystem()) {
                $readMessage = $this->messageRepository->find($event->getMessageId());
                $this->webSocketService->send($data, $readMessage->getSender()->getId());

                return;
            }
            $this->webSocketService->send($data, $event->getRecipientId());
        } catch (\Exception $e) {
            $this->logger->error('Error sending data to WebSocket server', [
                'exception' => $e,
            ]);
        }
    }

    private function getCountChats(int $recipientId): int
    {
        return $this->messageRepository->countUnreadChatsForRecipient($recipientId);
    }
}
