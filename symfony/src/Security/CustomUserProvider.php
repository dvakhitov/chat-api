<?php

namespace App\Security;

use App\Entity\User;
use App\Service\BoxgoAuthService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class CustomUserProvider implements UserProviderInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly BoxgoAuthService $boxgoAuthService,
        private readonly RequestStack $requestStack
    ) {}

    public function loadUserByIdentifier(string $userIdentifier): UserInterface
    {
        // Пытаемся найти пользователя в базе по идентификатору (например, по ID или email)
        $user = $this->entityManager->getRepository(User::class)->find($userIdentifier);
        if (!$user) {
            // Если пользователь не найден, пытаемся извлечь JWT-токен из текущего запроса
            $token = null;
            $request = $this->requestStack->getCurrentRequest();
            if ($request) {
                $authHeader = $request->headers->get('Authorization');
                if ($authHeader && preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
                    $token = $matches[1];
                }
            }
            // Создаем нового пользователя с помощью BoxgoAuthService
            $user = $this->boxgoAuthService->getNewUser($token, $userIdentifier);
            if (!$user) {
                throw new UserNotFoundException(sprintf('User with identifier "%s" not found and could not be created.', $userIdentifier));
            }
            $this->entityManager->persist($user);
            $this->entityManager->flush();
        }
        return $user;
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        // При необходимости обновляем данные пользователя из базы
        return $this->loadUserByIdentifier($user->getUserIdentifier());
    }

    public function supportsClass(string $class): bool
    {
        return $class === User::class || is_subclass_of($class, User::class);
    }
}
