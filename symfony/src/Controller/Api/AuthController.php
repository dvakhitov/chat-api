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

            $user = $boxGoUserService->getBoxgoUser($token);

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
