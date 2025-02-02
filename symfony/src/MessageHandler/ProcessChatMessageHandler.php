<?php

namespace App\MessageHandler;

use App\Message\ProcessChatMessage;
use App\Service\MessageProcessor;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
class ProcessChatMessageHandler
{
    public function __construct(
        private MessageProcessor $messageProcessor
    ) {}

    public function __invoke(ProcessChatMessage $message): void
    {
        $this->messageProcessor->process($message->getMessageData());
    }
} 
