<?php

namespace App\Service\MessageHandler;

use App\DTO\MessageHandlerResultDTO;
use App\DTO\ProcessMessageDTO;

interface MessageHandlerInterface
{
    public function handle(ProcessMessageDTO $messageData): MessageHandlerResultDTO;
} 
