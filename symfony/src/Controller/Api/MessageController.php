<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Message\AllMessagesProcessMessage;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api')]
class MessageController extends AbstractController
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly MessageBusInterface $messageBus,
    ) {
    }

    #[Route('/messages', name: 'api_messages_create', methods: ['POST'])]
    public function sendChatMessage(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $chatMessageData = json_decode($request->getContent(), true);

        if ($chatMessageData['chatPartnerId'] === $user->getId()) {
            /**
             * Если получатель и отправитель один и тот же, то никак не реагируем.
             * Просто не отправляем ничего, но не возвращаем ошибку.
             */
            $this->logger->error(
                sprintf(
                    'Trying to send message to self. %s. %s',
                    __METHOD__,
                    __LINE__
                ),
                [
                    'data' => $chatMessageData,
                ]
            );

            return $this->json(['status' => 'Message accepted'], Response::HTTP_ACCEPTED);
        }

        $this->logger->info('Message received', [
            'data' => $chatMessageData,
            'user' => $user->getId(),
            'ip' => $request->getClientIp(),
            'user-agent' => $request->headers->get('User-Agent') ?? ''
        ]);

        $message = new AllMessagesProcessMessage($chatMessageData, $user->getId());

        try {
            $this->messageBus->dispatch($message);
        } catch (\Exception $e) {

            $this->logger->error('Error sending message', [
                'exception' => $e,
            ]);

            return $this->json(['error' => 'Error sending message'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        // Возвращаем немедленное подтверждение получения
        return $this->json(['status' => 'Message accepted'], Response::HTTP_ACCEPTED);
    }
}
