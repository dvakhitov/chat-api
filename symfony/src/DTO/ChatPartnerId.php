<?php

namespace App\DTO;

trait ChatPartnerId
{

    public int $chatPartnerId {
        get => $this->chatPartnerId;
        set {
            $this->chatPartnerId = $value;
        }
    }

    public function getChatPartnerId(): int
    {
        return $this->chatPartnerId;
    }
}