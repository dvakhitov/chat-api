<?php

namespace App\Service;


use App\DTO\ChatMessageContentDTO;
use App\DTO\ChatMessageDtoInterface;
use App\DTO\ChatMessageReadDTO;
use App\Entity\User;
use App\Message\ProcessChatMessage;
use App\Repository\MessageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class MessageService
{
    public function __construct(
        private readonly MessageBusInterface $messageBus,
        private readonly MessageRepository $messageRepository,
        private readonly EntityManagerInterface $entityManager,
        private LoggerInterface $logger
    ) {
    }

    /**
     * @throws ExceptionInterface
     */
    public function sendMessage(Request $request, User $sender)
    {
        $data = json_decode($request->getContent(), true);

        $data['senderId'] = $sender->getId();
        if (isset($data['content'])) {
            $dataDto = $this->createContentDto($data);
        } elseif (isset($data['id'])) {
            $dataDto = $this->createReadDto($data);
        } else {
            throw new BadRequestException('Invalid data');
        }


        if (is_array($dataDto)) {
            $this->logger->debug(sprintf('[dataDto: %s]', json_encode($dataDto)));
            throw new BadRequestException('Invalid data');
        }
        // Отправляем сообщение в очередь для асинхронной обработки
        $this->messageBus->dispatch(new ProcessChatMessage($dataDto));
    }

    private function createContentDto(array $data): ChatMessageContentDTO
    {
        if (is_string($data['localId'])) {
            $this->logger->debug(sprintf('[localId: is string] = %s', $data['localId']));
        }
        $dataDto = new ChatMessageContentDTO();
        $this->fillChatMessageDto($dataDto, $data);

        return $dataDto;
    }

    private function createReadDto(array $data): ChatMessageReadDTO
    {
        $dataDto = new ChatMessageReadDTO();
        $dataDto->id = $data['id'];
        $dataDto->chatPartnerId = $data['chatPartnerId'];
        $message = $this->messageRepository->find($data['id']);
        $message->setIsRead(true);
        $this->entityManager->flush();
        $data['content'] = $message->getContent();
        $this->fillChatMessageDto($dataDto, $data);

        return $dataDto;
    }

    private function fillChatMessageDto(ChatMessageDtoInterface $dataDto, array $data): void
    {
        $dataDto->content = $data['content'];
        $dataDto->returnUniqId = (int)$data['localId'];
        $dataDto->chatPartnerId = $data['chatPartnerId'];
        $dataDto->sender = $data['senderId'];
        $dataDto->recipient = $data['chatPartnerId'];
        $dataDto->senderName = $data['senderName'] ?? 'Unknown Sender';
    }
}
