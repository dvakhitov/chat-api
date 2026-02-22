<?php

namespace App\Tests\Unit\Entity;

use App\Entity\User;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    public function testGetRolesReturnsRoleUser(): void
    {
        $user = new User();

        $roles = $user->getRoles();

        $this->assertContains('ROLE_USER', $roles);
    }

    public function testGetUserIdentifierReturnsEmail(): void
    {
        $user = new User();
        $user->setEmail('test@example.com');

        $this->assertEquals('test@example.com', $user->getUserIdentifier());
    }

    public function testSetAndGetFirstName(): void
    {
        $user = new User();
        $user->setFirstName('John');

        $this->assertEquals('John', $user->getFirstName());
    }

    public function testSetAndGetLastName(): void
    {
        $user = new User();
        $user->setLastName('Doe');

        $this->assertEquals('Doe', $user->getLastName());
    }

    public function testSetAndGetEmail(): void
    {
        $user = new User();
        $user->setEmail('john@example.com');

        $this->assertEquals('john@example.com', $user->getEmail());
    }

    public function testIsPremiumDefaultsToFalse(): void
    {
        $user = new User();

        $this->assertFalse($user->isPremium());
    }

    public function testSetAndGetIsPremium(): void
    {
        $user = new User();
        $user->setIsPremium(true);

        $this->assertTrue($user->isPremium());
    }

    public function testSetAndGetPhotoUrl(): void
    {
        $user = new User();
        $url = 'https://example.com/photo.jpg';

        $user->setPhotoUrl($url);

        $this->assertEquals($url, $user->getPhotoUrl());
    }

    public function testSetAndGetBirthday(): void
    {
        $user = new User();
        $birthday = new \DateTime('1990-05-15');

        $user->setBirthday($birthday);

        $this->assertEquals($birthday, $user->getBirthday());
    }

    public function testSetAndGetIsEmailVerified(): void
    {
        $user = new User();

        $user->setIsEmailVerified(true);

        $this->assertTrue($user->isEmailVerified());
    }

    public function testEraseCredentialsDoesNotThrow(): void
    {
        $user = new User();

        $user->eraseCredentials();

        $this->assertTrue(true);
    }

    public function testSetIdAndGetId(): void
    {
        $user = new User();
        $user->setId(42);

        $this->assertEquals(42, $user->getId());
    }
}
