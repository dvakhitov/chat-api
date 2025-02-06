<?php

namespace App\DTO\Api\History\Chat;

class ChatContentDTO
{
    public ChatPartnerDTO $chatPartner;

    public LastMessageDTO $lastMessage;

    public int $numberUnreadMessages;

    public int $numberUnreadTimeStamp;
}
