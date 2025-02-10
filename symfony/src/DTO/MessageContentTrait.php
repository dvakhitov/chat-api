<?php

namespace App\DTO;

trait MessageContentTrait
{
    public ?int $recipient = null;
    public ?string $senderName = null;

    public ?int $returnUniqId;

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