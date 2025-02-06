<?php

namespace App\DTO\Api\History\Chat;

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
