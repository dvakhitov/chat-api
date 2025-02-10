<?php

namespace App\Message;

use App\DTO\NotificationMessage\AbstractNotificationMessageDTO;

class NotificationMessage
{
    public function __construct(
        public AbstractNotificationMessageDTO $data,
        public int $notificationRecipientId,
        public bool $isSystem = false
    ) {
    }
} 
