<?php

namespace App\Service\MessageHandler;

use App\DTO\MessageHandlerResultDTO;
use App\DTO\ProcessMessageDTO;

class SystemMessageHandler implements MessageHandlerInterface
{
    public function handle(ProcessMessageDTO $messageData): MessageHandlerResultDTO
    {
        // Здесь логика обработки личных сообщений
        return new MessageHandlerResultDTO(); //todo: доработать создание
    }
} 
