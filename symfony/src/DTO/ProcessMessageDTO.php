<?php

namespace App\DTO;

class ProcessMessageDTO
{
    public ?int $recipient = null;
    public ?string $senderName = null;

    public int $returnUniqId {
        get => $this->returnUniqId;

        set {
            $this->returnUniqId = $value;
        }
    }

    public string $content {
        get => $this->content;
        set {
            $this->content = $value;
        }
    }

    public string $type {
        get => $this->type;
        set {
            $this->type = $value;
        }
    }

    public int $chatPartnerId {
        get => $this->chatPartnerId;
        set {
            $this->chatPartnerId = $value;
        }
    }

    public int $sender {
        get => $this->sender;
        set {
            $this->sender = $value;
        }
    }
}