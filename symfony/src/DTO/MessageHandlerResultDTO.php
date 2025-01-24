<?php

namespace App\DTO;

use App\DTO\NotificationMessage\AbstractNotificationMessageDTO;

class MessageHandlerResultDTO
{
    /**
     * @var AbstractNotificationMessageDTO[]
     */
   public array $notifications = [];
}