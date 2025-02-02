<?php

namespace App\Controller\Api;

use App\DTO\ProcessMessageDTO;
use App\Message\ProcessChatMessage;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api')]
class MessageController extends AbstractController
{
    public function __construct(
        private MessageBusInterface $messageBus
    ) {}

    /**
     * @throws ExceptionInterface
     */
    #[Route('/messages', name: 'api_messages_create', methods: ['POST'])]
    public function createMessage(Request $request): Response
    {
        $data = json_decode($request->getContent(), true);

        $content = $data['content'] ?? null;
        if (!$content) {
            return $this->json(['error' => 'Content is required'], Response::HTTP_BAD_REQUEST);
        }

        $dataDto = new ProcessMessageDTO();
        $dataDto->content = $content;
        $dataDto->returnUniqId = $data['localId'];
        $dataDto->chatPartnerId = $data['chatPartnerId'];
        $dataDto->sender = $data['senderId'];
        $dataDto->type = $this->getMessageType($data);
        $dataDto->recipient = $data['chatPartnerId'];
        $dataDto->senderName = $data['senderName'] ?? 'Unknown Sender';

        // Отправляем сообщение в очередь для асинхронной обработки
        $this->messageBus->dispatch(new ProcessChatMessage($dataDto));

        // Возвращаем немедленное подтверждение получения
        return $this->json(['status' => 'Message accepted'], Response::HTTP_ACCEPTED);
    }

    private function getMessageType(array $data): string
    {
        return 'message';
    }
}
