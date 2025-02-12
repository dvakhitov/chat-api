<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\ChatRepository;

class CountUnreadChatsService
{
    public function __construct(private ChatRepository $chatRepository)
    {
    }

    public function countUsersUnreadChats(int|User $user): int
    {
        return $this->chatRepository->getUnreadMessagesChatsCount($user);
    }
}
