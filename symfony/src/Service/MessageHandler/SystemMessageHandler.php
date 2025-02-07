<?php

namespace App\Service\MessageHandler;

use App\DTO\ChatMessageDtoInterface;
use App\DTO\MessageHandlerResultDTO;
use App\Entity\Message;
use App\Factory\MessageHandlerResultDTOFactory;
use App\Repository\MessageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Doctrine\DBAL\Exception\DeadlockException;

class SystemMessageHandler implements MessageHandlerInterface
{
    const MAX_RETRIES = 5;
    public function __construct(
        private MessageRepository $messageRepository,
        private MessageHandlerResultDTOFactory $resultDtoFactory,
        private LoggerInterface $logger,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function handle(ChatMessageDtoInterface $messageData): MessageHandlerResultDTO
    {
        /** @var Message $message */
        $message = $this->messageRepository->find($messageData->id);

        if (!$message) {
            throw new \RuntimeException(sprintf('Message not found. s%', __METHOD__));
        }

        $attempts = 0;
        while ($attempts < self::MAX_RETRIES) {
            try {
                $this->entityManager->beginTransaction();
                $this->messageRepository->setIsReadByUser(
                    $message->getRecipient()->getId(),
                    $message->getChat()->getId()
                );
                $this->entityManager->commit();
                return $this->resultDtoFactory->create(
                    $messageData,
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
                usleep(100000);
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
