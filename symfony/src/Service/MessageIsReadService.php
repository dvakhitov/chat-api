<?php

namespace App\Service;

use App\Entity\Message;
use App\Factory\MessageHandlerResultDTOFactory;
use App\Repository\MessageRepository;
use Doctrine\DBAL\Exception\DeadlockException;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class MessageIsReadService
{
    const int MAX_RETRIES = 5;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private MessageRepository $messageRepository,
        private MessageHandlerResultDTOFactory $resultDtoFactory,
        private LoggerInterface $logger,
    ) {
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