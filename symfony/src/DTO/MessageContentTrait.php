<?php

namespace App\DTO;

trait MessageContentTrait
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


    public int $sender {
        get => $this->sender;
        set {
            $this->sender = $value;
        }
    }
}