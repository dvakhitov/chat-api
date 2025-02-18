<?php

namespace App\Service;

use App\DTO\ChatMessageContentDTO;
use App\DTO\ChatMessageDtoInterface;
use App\DTO\ChatMessageReadDTO;
use App\Entity\Message;
use App\Repository\MessageRepository;
use App\Repository\UserRepository;
use Doctrine\DBAL\Exception\DeadlockException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\Messenger\Exception\ExceptionInterface;

class MessageService
{
    public function __construct(
        private readonly MessageRepository $messageRepository,
        private readonly UserRepository $userRepository,
        private LoggerInterface $logger,
        private readonly MessageIsReadService $messageIsReadService,
        private MessageProcessor $messageProcessor
    ) {
    }

    /**
     * @param array $sendingMessageData
     * @param int $chatMessageSenderId
     * @return void
     */
    public function sendMessage(array $sendingMessageData, int $chatMessageSenderId)
    {
        $chatMessageSender = $this->userRepository->find($chatMessageSenderId);

        try {
            $sendingMessageData['senderId'] = $chatMessageSender->getId();
            if (isset($sendingMessageData['content'])) {
                $dataDto = $this->createContentDto($sendingMessageData);
            } elseif (isset($sendingMessageData['id'])) {
                $dataDto = $this->createReadDto($sendingMessageData);
            } else {
                throw new BadRequestException('Invalid data');
            }
        } catch (\Throwable $th) {
           $this->logger->error(sprintf('Error sending message: %s', $th->getMessage() ), []);
        }

        if (is_array($dataDto)) {
            $this->logger->debug(sprintf('[dataDto: %s]', json_encode($dataDto)));
            throw new BadRequestException('Invalid data');
        }

        $this->messageProcessor->process($dataDto);
    }

    private function createContentDto(array $sendingMessageData): ChatMessageContentDTO
    {
        ;
        if (is_string($sendingMessageData['localId'])) {
            $this->logger->debug(sprintf('[localId: is string] = %s', $sendingMessageData['localId']));
        }

        $dataDto = new ChatMessageContentDTO();
        $this->fillChatMessageDto($dataDto, $sendingMessageData);


        return $dataDto;
    }

    /**
     * @throws DeadlockException
     */
    private function createReadDto(array $data): ChatMessageReadDTO
    {
        /** @var Message $message */
        $message = $this->messageRepository->find($data['id']);

        if (!$message) {
            throw new BadRequestException(sprintf('Invalid data: %s, line: %s', __METHOD__, __LINE__));
        }

        $dataDto = new ChatMessageReadDTO();
        $dataDto->id = $data['id'];
        $dataDto->chatPartnerId = $data['chatPartnerId'];

        $data['content'] = $message->getContent();

        $this->fillChatMessageDto($dataDto, $data, $message);

        $this->messageIsReadService->setMessagesOfTheChatIsRead($message);

        return $dataDto;
    }

    private function fillChatMessageDto(
        ChatMessageDtoInterface $dataDto,
        array $sendingMessageData,
        ?Message $message = null
    ): void {
        $dataDto->content = $sendingMessageData['content'];
        if (isset($sendingMessageData['localId'])) {
            $dataDto->returnUniqId = $sendingMessageData['localId'];
        } elseif ($message) {
            $dataDto->returnUniqId = $message->getLocalId();
        } else {
            $dataDto->returnUniqId = null;
        }

        $dataDto->chatPartnerId = $sendingMessageData['chatPartnerId'];
        $dataDto->sender = isset($message) ? $message->getSender()->getId() : $sendingMessageData['senderId'];
        $dataDto->recipient = isset($message) ? $message->getRecipient()?->getId() : $sendingMessageData['chatPartnerId'];
        $dataDto->senderName = $sendingMessageData['senderName'] ?? 'Unknown Sender';
        if ($dataDto->returnUniqId === null) {
            unset($dataDto->returnUniqId);
        }
    }
}
