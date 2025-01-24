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

        $dataDto = new ProcessMessageDTO();
        $dataDto->content = $data['content'];
        $dataDto->returnUniqId = $data['returnUniqId'];
        $dataDto->chatPartnerId = $data['chatPartnerId'];
        $dataDto->sender = $data['senderId'];
        $dataDto->type = $data['type'];

        // Отправляем сообщение в очередь для асинхронной обработки
        $this->messageBus->dispatch(new ProcessChatMessage($dataDto));

        // Возвращаем немедленное подтверждение получения
        return $this->json(['status' => 'Message accepted'], Response::HTTP_ACCEPTED);
    }
}
