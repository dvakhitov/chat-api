<?php

namespace App\DTO\NotificationMessage;

use App\DTO\ChatPartnerDTO;

class AbstractNotificationMessageDTO
{
    public string $type = 'message';

    public ChatPartnerDTO $chatPartner;

    public LastMessageDto $lastMessage;

    public int $numberUnreadMessages = 0;

    public int $numberUnreadTimeStamp;
}
