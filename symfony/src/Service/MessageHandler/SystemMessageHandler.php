<?php

namespace App\Service\MessageHandler;

use App\DTO\ChatMessageDtoInterface;
use App\DTO\MessageHandlerResultDTO;
use App\Entity\Message;
use App\Factory\MessageHandlerResultDTOFactory;
use App\Repository\MessageRepository;
use Psr\Log\LoggerInterface;

class SystemMessageHandler implements MessageHandlerInterface
{
    public function __construct(
        private MessageRepository $messageRepository,
        private MessageHandlerResultDTOFactory $resultDtoFactory,
        private LoggerInterface $logger
    ) {
    }

    public function handle(ChatMessageDtoInterface $messageData): MessageHandlerResultDTO
    {
        /** @var Message $message */
        $message = $this->messageRepository->find($messageData->id);

        if (!$message) {
            throw new \RuntimeException(sprintf('Message not found. s%', __METHOD__));
        }

        try {
            $this->messageRepository->setIsReadByUser($message->getRecipient()->getId(), $message->getChat()->getId());
            return $this->resultDtoFactory->create(
                $messageData,
                $message->getChat(),
                $message
            );
        } catch (\Throwable $e) {
            $this->logger->error(
                sprintf(
                    'Error processing message updating: %s. %s',
                    $e->getMessage(),
                    __METHOD__
                )
            );

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
