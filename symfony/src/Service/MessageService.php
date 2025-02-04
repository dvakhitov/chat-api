<?php

namespace App\Service;


use App\DTO\ChatMessageContentDTO;
use App\DTO\ChatMessageReadDTO;
use App\Message\ProcessChatMessage;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class MessageService
{
    public function __construct(
        private readonly MessageBusInterface $messageBus
    ) {
    }

    /**
     * @throws ExceptionInterface
     */
    public function sendMessage(Request $request)
    {
        $data = json_decode($request->getContent(), true);

        if (isset($data['content'])) {
            $dataDto = $this->createContentDto($data);
        } elseif (isset($data['id'])) {
            $dataDto = $this->createReadDto($data);
        } else {
            throw new BadRequestException('Invalid data');
        }

        // Отправляем сообщение в очередь для асинхронной обработки
        $this->messageBus->dispatch(new ProcessChatMessage($dataDto));
    }

    private function getMessageType(array $data): string
    {
        return 'message';
    }

    private function createContentDto(array $data): ChatMessageContentDTO
    {
        $dataDto = new ChatMessageContentDTO();
        $dataDto->content = $data['content'];
        $dataDto->returnUniqId = $data['localId'];
        $dataDto->chatPartnerId = $data['chatPartnerId'];
        $dataDto->sender = $data['senderId'];
        $dataDto->type = $this->getMessageType($data);
        $dataDto->recipient = $data['chatPartnerId'];
        $dataDto->senderName = $data['senderName'] ?? 'Unknown Sender';

        return $dataDto;
    }

    private function createReadDto(array $data): ChatMessageReadDTO
    {
        $datDto = new ChatMessageReadDTO();
        $datDto->id = $data['id'];
        $datDto->type = 'system';
        $datDto->chatPartnerId = $data['chatPartnerId'];

        return $datDto;
    }
}