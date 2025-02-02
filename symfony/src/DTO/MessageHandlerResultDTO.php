<?php

namespace App\DTO;

use App\DTO\NotificationMessage\AbstractNotificationMessageDTO;

class MessageHandlerResultDTO
{
    /**
     * @Groups({"websocket"})
     */
    public string $type;

    /**
     * @Groups({"websocket"})
     */
    public ChatPartnerDTO $chatPartner;

    /**
     * @Groups({"websocket"})
     */
    public MessageDTO $lastMessage;

    /**
     * @Groups({"websocket"})
     */
    public int $numberUnreadMessages;

    /**
     * @Groups({"websocket"})
     */
    public int $numberUnreadTimeStamp;

    public int $messageId;
    
    public int $chatId;
    /**
     * @var AbstractNotificationMessageDTO[]
     */
   public array $notifications = [];
}
