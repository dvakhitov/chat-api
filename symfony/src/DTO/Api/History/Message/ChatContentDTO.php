<?php

namespace App\DTO\Api\History\Message;

class ChatContentDTO
{
    public ChatPartnerDTO $chatPartner;

    public LastMessageDTO $lastMessage;

    public int $numberUnreadMessages;

    public int $numberUnreadTimeStamp;
}
