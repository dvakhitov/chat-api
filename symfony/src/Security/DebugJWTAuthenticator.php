<?php
// src/Security/DebugJWTAuthenticator.php

namespace App\Security;

use App\Entity\User;
use App\Factory\UserFactory;
use App\Service\BoxgoAuthService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\HttpFoundation\Response;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use Psr\Log\LoggerInterface;
use Throwable;

class DebugJWTAuthenticator extends AbstractAuthenticator
{
    private JWTEncoderInterface $jwtEncoder;
    private LoggerInterface $logger;

    public function __construct(
        private BoxgoAuthService $boxgoAuthService,
        private EntityManagerInterface $entityManager,
        JWTEncoderInterface $jwtEncoder,
        LoggerInterface $logger,
    ) {
        $this->jwtEncoder = $jwtEncoder;
        $this->logger = $logger;
    }

    public function supports(Request $request): ?bool
    {
        // Аутентификатор срабатывает, если есть заголовок Authorization с Bearer-токеном
        $authHeader = $request->headers->get('Authorization');
        return $authHeader && str_starts_with($authHeader, 'Bearer ');
    }

    public function authenticate(Request $request): SelfValidatingPassport
    {
        $authHeader = $request->headers->get('Authorization');
        if (!preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            throw new AuthenticationException('JWT токен не найден в заголовке Authorization');
        }

        $token = $matches[1];

        try {
            // Попытка декодирования токена
            $payload = $this->jwtEncoder->decode($token);
        } catch (\Exception $e) {
            // Логирование ошибки для отладки
            $this->logger->error('Ошибка декодирования JWT: ' . $e->getMessage(), ['exception' => $e]);
            throw new AuthenticationException('Ошибка декодирования JWT: ' . $e->getMessage());
        }

        // Для отладки можно залогировать payload
        $this->logger->info('JWT payload успешно декодирован', $payload);

        // Здесь предполагается, что в payload есть поле 'user_id' (или другое, например, userId)
        $userId = $payload['user_id'] ?? $payload['userId'] ?? null;
        if (!$userId) {
            throw new AuthenticationException('JWT не содержит идентификатора пользователя');
        }

        // Используем UserBadge с ленивой загрузкой пользователя.
        return new SelfValidatingPassport(new UserBadge($userId, function ($userIdentifier) use ($token) {
            // Здесь можно реализовать загрузку пользователя из базы или вернуть фиктивного пользователя для отладки.
            // Например:
             $user =  $this->entityManager->getRepository(User::class)->find($userIdentifier);
             if (!$user) {
                 $user = $this->boxgoAuthService->getNewUser($token, $userIdentifier);
                 $this->entityManager->persist($user);
                 $this->entityManager->flush();
             }

             return $user;

        }));
    }

    public function onAuthenticationSuccess(Request $request, $token, string $firewallName): ?Response
    {
        // При успешной аутентификации продолжаем выполнение запроса
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        // Возвращаем подробный ответ с информацией об ошибке для отладки
        return new JsonResponse([
            'error' => 'Аутентификация не пройдена',
            'message' => $exception->getMessage(),
        ], Response::HTTP_UNAUTHORIZED);
    }

    public function start(Request $request, ?AuthenticationException $authException = null): Response
    {
        return new JsonResponse([
            'error' => 'Аутентификация обязательна',
        ], Response::HTTP_UNAUTHORIZED);
    }

}
