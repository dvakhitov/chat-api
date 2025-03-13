<?php

namespace App\Security;

use App\Repository\UserRepository;
use App\Service\BoxGo\BoxGoUserService;
use App\Service\UserService;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\CustomCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

class JwtAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        private JWTTokenManagerInterface $jwtManager,
        private UserRepository $userRepository,
        private BoxGoUserService $boxGoUserService,
        private UserService $userService
    ) {
    }

    /**
     * Проверяем, поддерживает ли аутентификатор данный запрос.
     */
    public function supports(Request $request): ?bool
    {
        $authHeader = $request->headers->get('Authorization', '');
        return str_starts_with($authHeader, 'Bearer ');
    }

    /**
     * Извлекаем и валидируем токен через LexikJWTAuthenticationBundle.
     */
    public function authenticate(Request $request): Passport
    {
        $authHeader = $request->headers->get('Authorization', '');
        $token = substr($authHeader, 7);

        // Используем метод decode() из JWTTokenManagerInterface
        $payload = $this->jwtManager->parse($token);
        if (!$payload) {
            throw new AuthenticationException('Invalid JWT token');
        }

        $userId = $payload['user_id'] ?? null;
        if (!$userId) {
            throw new AuthenticationException('Token is missing user_id');
        }

        return new Passport(
            new UserBadge($userId, function (string $userIdentifier) use ($token) {
                $user = $this->userRepository->findOneBy(['id' => $userIdentifier]);
                if (!$user) {
                    $boxgoData = $this->boxGoUserService->getBoxgoUser($token);
                    if ($boxgoData === null) {
                        return null;
                    }
                    $user = $this->userService->createUserFromBoxgoData($boxgoData);
                }

                return $user;
            }),
            new CustomCredentials(static fn() => true, null)
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new JsonResponse([
            'error' => 'Unauthorized',
            'message' => $exception->getMessage()
        ], 401);
    }
}
