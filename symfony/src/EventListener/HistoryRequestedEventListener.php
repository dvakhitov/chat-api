<?php

namespace App\EventListener;

use App\Event\HistoryRequestedEvent;
use App\Service\HistoryRequestedService;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

final readonly class HistoryRequestedEventListener
{
    public function __construct(
        private HistoryRequestedService $historyRequestedService,
        private LoggerInterface $logger,
    ) {
    }

    #[AsEventListener(event: HistoryRequestedEvent::class)]
    public function onNotificationsSentEventListener(HistoryRequestedEvent $event): void
    {
        try {
            //в будущем нужно будет обрабатывать в очереди.
            $this->historyRequestedService->handle($event->getChat(), $event->getRecipient());
        } catch (\Throwable $exception) {
            $this->logger->error(
                sprintf(
                    'Error handling set isRead to messages: %s. %s',
                    __METHOD__,
                    $exception->getMessage()
                )
            );
        }
    }
}
