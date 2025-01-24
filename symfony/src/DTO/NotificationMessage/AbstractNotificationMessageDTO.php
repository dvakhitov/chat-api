<?php

namespace App\DTO\NotificationMessage;

use App\Entity\Message;
use App\DTO\ChatPartnerDTO;

class AbstractNotificationMessageDTO
{
    public string $type;

    public ChatPartnerDTO $chatPartner;

    public Message $lastMessage;

    public int $numberUnreadMessages = 0;

    public int $numberUnreadTimeStamp;

}
