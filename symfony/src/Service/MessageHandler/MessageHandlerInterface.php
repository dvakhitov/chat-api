<?php

namespace App\Service\MessageHandler;

use App\DTO\MessageHandlerResultDTO;
use App\DTO\ChatMessageDtoInterface;

interface MessageHandlerInterface
{
    public function handle(ChatMessageDtoInterface $messageData): MessageHandlerResultDTO;
} 
