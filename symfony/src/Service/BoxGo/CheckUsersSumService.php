<?php

declare(strict_types=1);

namespace App\Service\BoxGo;

use App\Entity\User;

class CheckUsersSumService
{
    public function check(User $user, array $boxgoData)
    {
        return $this->getUserObjectHash($user) === $this->getUserArrayHash($boxgoData);
    }

    /**
     * @throws \SodiumException
     */
    private function getUserObjectHash(User $user): string
    {

        $string = sprintf(
            '%s%s%s%s%s%s%s',
            $user->getId(),
            $user->getEmail() ?? '',
            $user->getFirstName(),
            $user->getLastName(),
            $user->getBirthday() ?? '',
            $user->isEmailVerified() ? 'true' : 'false',
            $user->getCreatedAt()->format('Y-m-d')
        );

        return $user->getId() . md5($string);
    }

    /**
     * @throws \SodiumException
     */
    private function getUserArrayHash(array $boxgoData): string
    {
        $string = sprintf(
            '%s%s%s%s%s%s%s',
            $boxgoData['id'],
            $boxgoData['email'] ?? '',
            $boxgoData['firstName'],
            $boxgoData['lastName'],
            $boxgoData['photoUrl'] ?? '',
            $boxgoData['birthday'] ?? '',
            $boxgoData['isEmailVerified'] ? 'false' : 'false',
            $boxgoData['createdDate'] ?? '',
        );

        return $boxgoData['id'] . md5($string);
    }
}
