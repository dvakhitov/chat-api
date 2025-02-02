<?php

namespace App\DTO;

use Symfony\Component\Serializer\Annotation\Groups;

class MessageDTO
{
    /**
     * @Groups({"websocket"})
     */
    public int $id;

    /**
     * @Groups({"websocket"})
     */
    public int $senderId;

    /**
     * @Groups({"websocket"})
     */
    public \DateTimeInterface $createdDate;

    /**
     * @Groups({"websocket"})
     */
    public ?\DateTimeInterface $updatedDate = null;

    /**
     * @Groups({"websocket"})
     */
    public string $content;

    /**
     * @Groups({"websocket"})
     */
    public bool $isRead;
} 