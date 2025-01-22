<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\{Response, JsonResponse};
use Symfony\Component\Routing\Annotation\Route;
use App\Security\JWT;
use Symfony\Component\Uid\Uuid;

#[Route('/api/auth')]
class AuthController extends AbstractController
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly JWT $jwt,
    ) {
    }

    #[Route('/validate-token', name: 'api_auth_validate_token', methods: ['POST'])]
    public function validateToken(Request $request): Response
    {
        $token = $request->headers->get('Authorization');

        if (!$token || !str_starts_with($token, 'Bearer ')) {
            return $this->json(['error' => 'No token provided'], Response::HTTP_UNAUTHORIZED);
        }

        $token = substr($token, 7);

        try {
            $payload = $this->jwt->validate($token);

            // Используем username как email
            $user = $this->userRepository->findOneBy(['email' => $payload['username']]);


            try {
                if (!($user instanceof User)) {
                    // Создаем нового пользователя
                    $user = new User();
                    $user->setEmail($payload['username']);
                    $user->setFirstName($payload['firstname'] ?? $payload['username']);
                    $user->setLastName($payload['lastname'] ?? $payload['username']);

                    $user->setChatUserUuid(Uuid::v4());

                    $this->entityManager->persist($user);

                    $this->entityManager->flush();
                }
            } catch (\Throwable $exception) {
                dd($exception);
            }

            return $this->json([
                'connected' => true,
                'countNotifications' => 2, // TODO: Получать реальное количество уведомлений
                'countChats' => 0 // TODO: Получать реальное количество чатов
            ]);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNAUTHORIZED);
        }
    }
} 
