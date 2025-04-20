<?php

namespace App\Service\BoxGo;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class BoxGoUserService
{
    public function __construct(
        private readonly string $boxgoUrl,
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger,
        private readonly EntityManagerInterface $entityManager,
        private readonly UserRepository $userRepository,
        private readonly  CheckUsersSumService $checkUsersSumService,
    ) {
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function getBoxgoUser(string $token): ?User
    {
        $response = $this->httpClient->request('POST', $this->boxgoUrl, [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json',
            ]
        ]);

        if (!in_array($response->getStatusCode(), [200, 201])) {
            return null;
        }

        try {
            $userData = json_decode($response->getContent(), true);
            $user = $this->getUser($userData);
        } catch (ClientExceptionInterface|RedirectionExceptionInterface|ServerExceptionInterface|TransportExceptionInterface $e) {
            $this->logger->error($e);

            return null;
        }

        return $user;
    }

    private function getUser(array $userData): User
    {
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

        return $user;
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
