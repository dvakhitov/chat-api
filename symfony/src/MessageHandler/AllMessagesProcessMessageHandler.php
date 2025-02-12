<?php

namespace App\MessageHandler;

use App\Message\AllMessagesProcessMessage;
use App\Message\ProcessChatMessage;
use App\Service\MessageService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class AllMessagesProcessMessageHandler
{
    public function __construct(
        private readonly MessageService $messageService,
        private readonly LoggerInterface $logger
    ) {}

    public function __invoke(AllMessagesProcessMessage $message): void
    {
        try {
            $this->messageService->sendMessage($message->getRequestData(), $message->getChatMessageSenderId());
        } catch (\Exception $e) {
            $this->logger->error('Error sending message', [
                'exception' => $e,
            ]);

            $this->logger->error('Error sending message', [
                'execption' => $e,
            ]);
        }
    }
} 
