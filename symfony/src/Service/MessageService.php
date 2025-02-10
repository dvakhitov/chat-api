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
     * @throws ExceptionInterface
     */
    public function sendMessage(array $data, int $senderId)
    {
        $sender = $this->userRepository->find($senderId);

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

    /**
     * @throws DeadlockException
     */
    private function createReadDto(array $data): ChatMessageReadDTO
    {
        $dataDto = new ChatMessageReadDTO();
        $dataDto->id = $data['id'];
        $dataDto->chatPartnerId = $data['chatPartnerId'];
        /** @var Message $message */
        $message = $this->messageRepository->find($data['id']);
        $data['content'] = $message->getContent();
        $this->fillChatMessageDto($dataDto, $data, $message);

        $this->setMessagesOfTheChatIsRead($message);

        return $dataDto;
    }

    private function fillChatMessageDto(ChatMessageDtoInterface $dataDto, array $data, ?Message $message): void
    {
        $dataDto->content = $data['content'];
        $dataDto->returnUniqId = $message?->getLocalId();
        $dataDto->chatPartnerId = $data['chatPartnerId'];
        $dataDto->sender = $data['senderId'];
        $dataDto->recipient = $data['chatPartnerId'];
        $dataDto->senderName = $data['senderName'] ?? 'Unknown Sender';
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
                    $message->getChat(),
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
