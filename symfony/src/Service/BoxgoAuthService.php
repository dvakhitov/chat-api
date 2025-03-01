<?php

namespace App\Service;

use App\Entity\User;
use App\Factory\UserFactory;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

readonly class BoxgoAuthService
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private string $boxgoUrl,
        private LoggerInterface $logger,
        private UserFactory $userFactory,
    ) {
    }

    public function getNewUser(string $token, int $userId): ?User
    {
        $userData =  json_decode($this->getUserData($token, $userId), true);
        if (isset($userData['userData'])) {
            $userData = $userData['userData'][0];
        }
        if ($userData !== null) {
            if (isset($userData['userId'])) {
                $userData['id'] = $userData['userId'];
                return $this->userFactory->create($userData);
            }
        }

        return null;
    }

    public function getUserData(string $token, ?int $userId = null): null|string
    {
        $url = $this->boxgoUrl . '/api/users/get_auth_data';
        if (is_int($userId)) {
            $url .= '/' . $userId;
        }
        try {
            $response = $this->httpClient->request('POST', $url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ]
            ]);

            $this->logger->debug('Boxgo server response : ' . (string)$userId);
            $statusCode = $response->getStatusCode();
            $content = $response->getContent(false); // Не выбрасывает исключение для статусов 400 и выше

            if (200 !== $statusCode) {
                if (404 === $statusCode) {
                    return null;
                }
                $this->logger->error(
                    'BoxgoError: Response is not OK. Status code: {statusCode}. Content: {content}',
                    [
                        'statusCode' => $statusCode,
                        'content' => $content,
                    ]
                );

                return null;
            }
            $this->logger->info('WebSocket server response', [
                'statusCode' => $statusCode,
                'content' => $content,
            ]);

            return $content;
        } catch (\Exception $e) {
            $this->logger->error('Error sending data to WebSocket server', [
                'exception' => $e,
            ]);
        } catch (TransportExceptionInterface $e) {
            $this->logger->error('Error sending data to WebSocket server', []);
        }

        return null;
    }
} 
