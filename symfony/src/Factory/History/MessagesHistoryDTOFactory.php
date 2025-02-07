<?php

namespace App\Factory\History;

use App\DTO\Api\History\Message\MessagesHistoryDTO;
use App\DTO\Api\History\Message\MessagesHistoryItemDTO;
use App\Entity\Message;
use App\Entity\User;
use App\Helper\DateTimeHelper;

class MessagesHistoryDTOFactory
{
    /**
     * @param Message[] $messages
     * @return MessagesHistoryDTO
     */
    public function create(array $messages, int $chatPartnerId): MessagesHistoryDTO
    {
        $messagesHistoryDTO = new MessagesHistoryDTO();
        $messagesHistoryDTO->content = [];
        foreach ($messages as $message) {
            $messageHistoryItemDto = new MessagesHistoryItemDTO();
            $messageHistoryItemDto->id = $message->getId();
            $messageHistoryItemDto->chatPartnerId = $chatPartnerId;
            $messageHistoryItemDto->senderId = $message->getSender()->getId();
            $messageHistoryItemDto->createdDate = DateTimeHelper::formatWithTimezone($message->getCreatedAt());
            $messageHistoryItemDto->updatedDate = DateTimeHelper::formatWithTimezone($message->getUpdatedAt());
            $messageHistoryItemDto->content = $message->getContent();
            $messageHistoryItemDto->status = $message->getStatus();

            $messagesHistoryDTO->content[] = $messageHistoryItemDto;
        }

        return $messagesHistoryDTO;
    }
}
