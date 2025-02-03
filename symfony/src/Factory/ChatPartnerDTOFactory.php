<?php

namespace App\Factory;

use App\DTO\ChatPartnerDTO;
use App\Entity\ChatPartner;

class ChatPartnerDTOFactory
{
    public static function create(ChatPartner $chatPartner): ChatPartnerDTO
    {
        $dto = new ChatPartnerDTO();
        $dto->id = $chatPartner->getUser()->getId();
        $dto->userId = $chatPartner->getUser()->getId();
        $dto->email = $chatPartner->getUser()->getEmail();
        $dto->firstName = $chatPartner->getUser()->getFirstName();
        $dto->lastName = $chatPartner->getUser()->getLastName();
        $dto->emailVerified = $chatPartner->getUser()->getIsEmailVerified()?:false;
        $dto->createdDate = $chatPartner->getUser()->getCreatedAt();

        return $dto;
    }
}
