<?php

namespace App\Service\MessageHandler;

use App\DTO\ChatMessageDtoInterface;
use App\DTO\MessageHandlerResultDTO;

class SystemMessageHandler implements MessageHandlerInterface
{
    public function handle(ChatMessageDtoInterface $messageData): MessageHandlerResultDTO
    {
        // Здесь логика обработки личных сообщений
        return new MessageHandlerResultDTO(); //todo: доработать создание
    }
} 
