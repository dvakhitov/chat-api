<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Chat;
use App\Entity\Message;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

class MessageTest extends TestCase
{
    public function testGetStatusReturnsReadWhenMessageIsRead(): void
    {
        $message = new Message();
        $message->setIsRead(true);

        $this->assertEquals(Message::STATUS_READ, $message->getStatus());
    }

    public function testGetStatusReturnsSentWhenMessageIsNotRead(): void
    {
        $message = new Message();
        $message->setIsRead(false);

        $this->assertEquals(Message::STATUS_SENT, $message->getStatus());
    }

    public function testSetContentAndGetContent(): void
    {
        $message = new Message();
        $content = 'Hello, World!';

        $message->setContent($content);

        $this->assertEquals($content, $message->getContent());
    }

    public function testSetSenderAndGetSender(): void
    {
        $message = new Message();
        $user = new User();
        $user->setId(1);
        $user->setFirstName('John');

        $message->setSender($user);

        $this->assertSame($user, $message->getSender());
        $this->assertEquals(1, $message->getSender()->getId());
    }

    public function testSetRecipientAndGetRecipient(): void
    {
        $message = new Message();
        $user = new User();
        $user->setId(2);
        $user->setFirstName('Jane');

        $message->setRecipient($user);

        $this->assertSame($user, $message->getRecipient());
        $this->assertEquals(2, $message->getRecipient()->getId());
    }

    public function testSetChatAndGetChat(): void
    {
        $message = new Message();
        $chat = new Chat();

        $message->setChat($chat);

        $this->assertSame($chat, $message->getChat());
    }

    public function testSetLocalIdAndGetLocalId(): void
    {
        $message = new Message();
        $localId = '123456789';

        $message->setLocalId($localId);

        $this->assertEquals($localId, $message->getLocalId());
    }

    public function testFluentInterface(): void
    {
        $message = new Message();

        $result = $message
            ->setContent('Test')
            ->setIsRead(true)
            ->setLocalId('123');

        $this->assertInstanceOf(Message::class, $result);
    }
}
