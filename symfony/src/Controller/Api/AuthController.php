<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\BoxgoAuthService;
use App\Service\CountUnreadChatsService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\{Response, JsonResponse};
use Symfony\Component\Routing\Annotation\Route;
use App\Security\JWT;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;

#[Route('/api/auth')]
class AuthController extends AbstractController
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly CountUnreadChatsService $countUnreadChatsService,
        private readonly JWT $jwt,
        private readonly BoxgoAuthService $boxgoAuthService
    ) {
    }

    #[Route('/validate-token', name: 'api_auth_validate_token', methods: ['POST'])]
    public function validateToken(Request $request): Response
    {
        file_put_contents('php://stdout', "validateToken called\n");

        $token = $request->headers->get('Authorization');

        $token = substr($token, 7);

        try {
            $payload = $this->jwt->getPayload($token);

            $user = $this->userRepository->find($payload['user_id']);

            if (!($user instanceof User)) {
                $user = $this->boxgoAuthService->getNewUser($token, $payload['user_id']);
                if (!$user instanceof User) {
                    throw new UserNotFoundException('User not found');
                }
                $this->entityManager->persist($user);
                $this->entityManager->flush();
            }

            $data = [
                'connected' => true,
                'countChats' => $this->countUnreadChatsService->countUsersUnreadChats($user),
            ];

            $data['userId'] = $user->getId();

            return $this->json($data);
        } catch (UserNotFoundException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNAUTHORIZED);
        }
    }
} 
