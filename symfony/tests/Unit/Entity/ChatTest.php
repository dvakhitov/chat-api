<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Chat;
use App\Entity\ChatPartner;
use App\Entity\Message;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

class ChatTest extends TestCase
{
    public function testGetTypeDefaultsToPrivate(): void
    {
        $chat = new Chat();

        $this->assertEquals('private', $chat->getType());
    }

    public function testSetAndGetType(): void
    {
        $chat = new Chat();
        $chat->setType('group');

        $this->assertEquals('group', $chat->getType());
    }

    public function testSetAndGetLastMessage(): void
    {
        $chat = new Chat();
        $message = new Message();

        $chat->setLastMessage($message);

        $this->assertSame($message, $chat->getLastMessage());
    }

    public function testGetMessagesReturnsCollection(): void
    {
        $chat = new Chat();

        $this->assertCount(0, $chat->getMessages());
    }

    public function testGetChatPartnersReturnsCollection(): void
    {
        $chat = new Chat();

        $this->assertCount(0, $chat->getChatPartners());
    }

    public function testGetChatPartnerByUserIdReturnsNullWhenNotFound(): void
    {
        $chat = new Chat();

        $result = $chat->getChatPartnerByUserId(999);

        $this->assertNull($result);
    }

    public function testGetChatPartnerByUserIdReturnsChatPartner(): void
    {
        $chat = new Chat();

        $user = new User();
        $user->setId(123);

        $chatPartner = new ChatPartner();
        $chatPartner->setUser($user);
        $chatPartner->setChat($chat);

        $chat->setChatPartners([$chatPartner]);

        $result = $chat->getChatPartnerByUserId(123);

        $this->assertSame($chatPartner, $result);
    }

    public function testSetTypeReturnsFluentInterface(): void
    {
        $chat = new Chat();

        $result = $chat->setType('group');

        $this->assertInstanceOf(Chat::class, $result);
    }

    public function testSetLastMessageReturnsFluentInterface(): void
    {
        $chat = new Chat();

        $result = $chat->setLastMessage(new Message());

        $this->assertInstanceOf(Chat::class, $result);
    }
}
