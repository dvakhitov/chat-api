<?php

namespace App\DTO;

class ChatPartnerDTO
{
    public int $id;
    public int $userId;
    public string $userEmail;
    public ?string $userFirstName;
    public ?string $userLastName;
    public ?string $userPhotoUrl;
} 
