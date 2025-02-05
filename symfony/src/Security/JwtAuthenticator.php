<?php

namespace App\Security;

use App\Repository\UserRepository;
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
        private JWT $jwt,
        private UserRepository $userRepository
    ) {
    }

    /**
     * Метод, который проверяет, «поддерживает ли» аутентификатор
     * текущий запрос (есть ли Bearer-токен и т.д.).
     *
     * Вернём true, если в заголовке Authorization есть Bearer <token>.
     */
    public function supports(Request $request): ?bool
    {
        $authHeader = $request->headers->get('Authorization', '');
        return str_starts_with($authHeader, 'Bearer ');
    }

    /**
     * Извлекаем токен, валидируем через JWT, и строим Passport.
     */
    public function authenticate(Request $request): Passport
    {
        $authHeader = $request->headers->get('Authorization', '');
        // Удаляем префикс "Bearer "
        $token = substr($authHeader, 7);

        try {
            // Валидируем токен, используя ваш класс JWT
            $payload = $this->jwt->validate($token);
        } catch (\InvalidArgumentException $e) {
            // Любые ошибки валидации выбрасываем как AuthenticationException
            throw new AuthenticationException($e->getMessage());
        }

        // Извлекаем username из payload
        $username = $payload['username'] ?? null;
        if (!$username) {
            throw new AuthenticationException('Token is missing username');
        }

        // Вариант 1: просто берем User из репозитория
        // и создаём SelfValidatingPassport.
        // Вариант 2: используем CustomCredentials, если хотим ещё раз что-то проверить.
        return new Passport(
            new UserBadge($username, function (string $userIdentifier) {
                // Допустим, вы храните email или username в БД
                return $this->userRepository->findOneBy(['email' => $userIdentifier]);
            }),
            // CustomCredentials позволяет, если нужно,
            // повторно выполнить проверку. Можно и SelfValidatingCredentials использовать.
            new CustomCredentials(
                function () {
                    // Здесь дополнительная проверка не нужна, т.к.
                    // мы уже всё проверили в JWT->validate().
                    return true;
                },
                null
            )
        );
    }

    /**
     * Если аутентификация прошла успешно,
     * вернуть null, чтобы запрос шёл дальше (к контроллеру).
     */
    public function onAuthenticationSuccess(
        Request $request,
        TokenInterface $token,
        string $firewallName
    ): ?Response {
        return null;
    }

    /**
     * Если аутентификация не прошла,
     * вернём JSON с 401 ошибкой.
     */
    public function onAuthenticationFailure(
        Request $request,
        AuthenticationException $exception
    ): ?Response {
        return new JsonResponse([
            'error' => 'Unauthorized',
            'message' => $exception->getMessage()
        ], 401);
    }
}
