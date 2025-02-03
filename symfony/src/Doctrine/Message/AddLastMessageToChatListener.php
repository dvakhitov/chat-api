<?php

namespace App\Doctrine\Message;

use App\Entity\Message;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Events;
#[AsEntityListener(event: Events::prePersist, method: 'addLastMessage', entity: Message::class)]
class AddLastMessageToChatListener
{
    public function addLastMessage(Message $message, PrePersistEventArgs  $args): void
    {
        $chat = $message->getChat();
        $chat->setLastMessage($message);
    }
}
