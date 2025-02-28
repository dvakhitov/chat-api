<?php

namespace App\Factory;

use App\Entity\User;

class UserFactory
{
    public function create(array $userData): User
    {
        $user = new User();
        $user->setId($userData['id']);
        if (isset($userData['email'])) {
            $user->setEmail($userData['email']);
        }
        if (isset($userData['firstName'])) {
            $user->setFirstName($userData['firstName']);
        }
        if (isset($userData['lastName'])) {
            $user->setLastName($userData['lastName']);
        }
        if (isset($userData['photoUrl'])) {
            $user->setPhotoUrl($userData['photoUrl']);
        }
        if (isset($userData['isPremium'])) {
            $user->setIsPremium($userData['isPremium']);
        }
        if (isset($userData['createdAt'])) {
            $user->setCreatedAt(new \DateTimeImmutable($userData['createdAt']));
        }
        if (isset($userData['updatedAt'])) {
            $user->setUpdatedAt(new \DateTime($userData['updatedAt']));
        }
        if (isset($userData['birthday'])) {
            $user->setBirthday($userData['birthday']);
        }
        if (isset($userData['isEmailVerified'])) {
            $user->setIsEmailVerified($userData['isEmailVerified']);
        }
        if (isset($userData['roles'])) {
            $user->setRoles($userData['roles']);
        }

        return $user;
    }
}