<?php

namespace App\EventListener;

use App\Event\NotificationsSentEvent;
use App\Repository\MessageRepository;
use App\Service\CountUnreadChatsService;
use App\Service\WebSocketService;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

final readonly class NotificationsSentEventListener
{
    public function __construct(
        private MessageRepository $messageRepository,
        private WebSocketService $webSocketService,
        private LoggerInterface $logger,
        private CountUnreadChatsService $countUnreadChatsService,
    ) {
    }

    /**
     * @throws \JsonException
     */
    #[AsEventListener(event: NotificationsSentEvent::class)]
    public function onNotificationsSentEventListener(NotificationsSentEvent $event): void
    {
        $data = [
            'type' => 'chat',
            'countChats' => $this->countUnreadChatsService
                ->countUsersUnreadChats($event->getRecipientId())
        ];
        $this->logger->info(sprintf('Sending data to WebSocket server: %s', json_encode($data, JSON_THROW_ON_ERROR)), ['data' => $data]);
        try {

            if ($event->isSystem()) {
                $readMessage = $this->messageRepository->find($event->getMessageId());
                $this->webSocketService->send($data, $readMessage->getSender()->getId());

                return;
            }
            $this->webSocketService->send($data, $event->getRecipientId());
        } catch (\Exception $e) {
            $this->logger->error(sprintf('Error sending data to WebSocket server: %s', $e->getMessage()), [
                'exception' => $e,
            ]);
        }
    }
}
