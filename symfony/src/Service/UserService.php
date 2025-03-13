<?php

namespace App\Service;

use App\Entity\User;

class UserService
{
    public function createUserFromBoxgoData(array $boxgoData): User
    {
        $user = new User();
        $this->setUsersProperties($user, $boxgoData);

        return $user;
    }

    private function setUsersProperties(User $user, array $userData): void
    {
        $createdDate = new \DateTimeImmutable($userData['createdDate']);

        $user->setId($userData['id']);
        $user->setEmail($userData['email'] ?? '');
        $user->setFirstName($userData['firstName'] ?? null);
        $user->setLastName($userData['lastName'] ?? null);
        $user->setBirthday($userData['birthday'] ?? null);
        $user->setCreatedAt($createdDate);
        $user->setPhotoUrl($userData['photoUrl'] ?? null);
        $user->setIsPremium($userData['isPremium'] ?? false);
        $user->setIsEmailVerified($userData['isEmailVerified'] ?? false);
    }
}