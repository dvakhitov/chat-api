<?php

namespace App\DTO\Api\History\Message;

class ChatPartnerDTO
{
    public int $id;

    public string $email;

    public string $firstName;

    public string $lastName;

    public ?string $photoUrl = null;

    public string $createdDate;

    public ?string $birthDate = null;

    public bool $emailVerified;
}
