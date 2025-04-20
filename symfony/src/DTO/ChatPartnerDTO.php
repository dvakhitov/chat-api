<?php

namespace App\DTO;

use App\Helper\DateTimeHelper;
use Symfony\Component\Serializer\Annotation\Groups;

class ChatPartnerDTO
{
    #[Groups(['websocket'])]
    public int $id;

    #[Groups(['websocket'])]
    public int $userId;

    #[Groups(['websocket'])]
    public string $email;

    #[Groups(['websocket'])]
    public string $photoUrl;

    #[Groups(['websocket'])]
    public ?string $firstName;

    #[Groups(['websocket'])]
    public ?string $lastName;

    #[Groups(['websocket'])]
    public string $createdDate {
        set (\DateTimeInterface|string $createdDate) {
            if ($createdDate instanceof \DateTimeInterface) {
                $this->createdDate = DateTimeHelper::formatWithTimezone($createdDate);
            }
            else {
                $this->createdDate = $createdDate;
            }
        }
    }

    #[Groups(['websocket'])]
    public bool $emailVerified = false;
} 
