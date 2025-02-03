<?php

namespace App\DTO\NotificationMessage;

use App\Helper\DateTimeHelper;

class LastMessageDto
{
    public int $id;

    public ?int $localId = null;

    public int $senderId;

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

    public string $updatedDate {
        set (\DateTimeInterface|string $updatedDate) {
            if ($updatedDate instanceof \DateTimeInterface) {
                $this->createdDate = DateTimeHelper::formatWithTimezone($updatedDate);
            } else {
                $this->createdDate = $updatedDate;
            }
        }
    }

    public string $content;

    public string $status = 'sent';
}
