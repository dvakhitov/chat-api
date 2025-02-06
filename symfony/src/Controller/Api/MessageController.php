<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Service\MessageService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api')]
class MessageController extends AbstractController
{
    public function __construct(
        private readonly MessageService $messageService,
        private readonly LoggerInterface $logger,
    ) {
    }

    #[Route('/messages', name: 'api_messages_create', methods: ['POST'])]
    public function createMessage(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        try {
            $this->messageService->sendMessage($request, $user);
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
