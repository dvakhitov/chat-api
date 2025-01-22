<?php

namespace App\Controller\Api;

use App\Message\ChatMessage;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api')]
class MessageController extends AbstractController
{
    public function __construct(
        private MessageBusInterface $messageBus
    ) {}

    #[Route('/messages', name: 'api_messages_create', methods: ['POST'])]
    public function createMessage(Request $request): Response
    {
        $data = json_decode($request->getContent(), true);
        
        $message = new ChatMessage(
            $data['type'],
            $data['sender'],
            $data['recipient'] ?? null,
            $data['message'],
            $data['timestamp']
        );

        $this->messageBus->dispatch($message);

        return $this->json(['status' => 'Message queued'], Response::HTTP_ACCEPTED);
    }
}
