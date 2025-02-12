<?php

namespace App\Service;

use App\DTO\ChatMessageContentDTO;
use App\DTO\ChatMessageDtoInterface;
use App\DTO\ChatMessageReadDTO;
use App\Entity\Message;
use App\Factory\MessageHandlerResultDTOFactory;
use App\Message\ProcessChatMessage;
use App\Repository\MessageRepository;
use App\Repository\UserRepository;
use Doctrine\DBAL\Exception\DeadlockException;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class MessageService
{
    const MAX_RETRIES = 5;

    public function __construct(
        private readonly MessageBusInterface $messageBus,
        private readonly MessageRepository $messageRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly UserRepository $userRepository,
        private LoggerInterface $logger,
        private MessageHandlerResultDTOFactory $resultDtoFactory,
    ) {
    }

    /**
     * @param array $sendingMessageData
     * @param int $chatMessageSenderId
     * @return void
     * @throws DeadlockException
     * @throws ExceptionInterface
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
        // Отправляем сообщение в очередь для асинхронной обработки
        $this->messageBus->dispatch(new ProcessChatMessage($dataDto));
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

        $dataDto = new ChatMessageReadDTO();
        $dataDto->id = $data['id'];
        $dataDto->chatPartnerId = $data['chatPartnerId'];

        $data['content'] = $message->getContent();
        $this->fillChatMessageDto($dataDto, $data, $message);

        $this->setMessagesOfTheChatIsRead($message);

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

    public function setMessagesOfTheChatIsRead(Message $message)
    {
        $attempts = 0;
        while ($attempts < self::MAX_RETRIES) {
            try {
                $this->entityManager->beginTransaction();
                $this->messageRepository->setIsReadByUser(
                    $message->getRecipient()->getId(),
                    $message->getChat()->getId(),
                );
                $this->entityManager->commit();

                return $this->resultDtoFactory->create(
                    $message
                );
            } catch (DeadlockException $e) {
                $this->entityManager->rollback();
                $attempts++;

                if ($attempts >= self::MAX_RETRIES) {
                    $this->logger->error(
                        sprintf(
                            '[Dedlock Exception]Error processing message updating: %s. %s',
                            $e->getMessage(),
                            __METHOD__
                        )
                    );
                    throw $e;
                }

                // Небольшая задержка перед повторной попыткой
                usleep(10000);
            } catch (\Throwable $e) {
                $this->logger->error(
                    sprintf(
                        'Error processing message updating: %s. %s',
                        $e->getMessage(),
                        __METHOD__
                    )
                );
                $this->entityManager->rollback();
                throw new \RuntimeException(
                    sprintf(
                        'Error processing message updating: %s. %s',
                        $e->getMessage(),
                        __METHOD__
                    ),
                    0,
                    $e
                );
            }
        }
    }
}
