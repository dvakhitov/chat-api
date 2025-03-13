<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\BoxGo\BoxGoUserService;
use App\Service\BoxGo\CheckUsersSumService;
use App\Service\BoxgoAuthService;
use App\Service\CountUnreadChatsService;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
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
        private JWTTokenManagerInterface $jwtManager,
        private readonly  CheckUsersSumService $checkUsersSumService,
        private readonly CountUnreadChatsService $countUnreadChatsService,
    ) {
    }

    #[Route('/validate-token', name: 'api_auth_validate_token', methods: ['POST'])]
    public function validateToken(
        Request $request,
        BoxGoUserService $boxGoUserService
    ): Response {
        file_put_contents('php://stdout', "validateToken called\n");

        $token = $request->headers->get('Authorization');

        $token = substr($token, 7);

        try {
            $payload = $this->jwtManager->parse($token);

            $userData = $boxGoUserService->getBoxgoUser($token);

            if (!isset($userData['id'])) {
                throw new \RuntimeException('User not found', Response::HTTP_NOT_FOUND);
            }

            $user =  $this->userRepository->find($userData['id']);
            if (!$user) {
                $user = $this->createUser($userData);
            } else {
                if (!$this->checkUsersSumService->check($user, $userData)) {
                    $this->setUsersProperties($user, $userData);
                }
            }

            $this->entityManager->flush();

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

    private function createUser(array $userData): User
    {
        $user = new User();

        $this->setUsersProperties($user, $userData);

        $this->entityManager->persist($user);

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
