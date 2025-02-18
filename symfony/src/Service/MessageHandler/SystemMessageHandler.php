<?php

namespace App\Service\MessageHandler;

use App\DTO\ChatMessageDtoInterface;
use App\DTO\MessageHandlerResultDTO;
use App\Entity\Message;
use App\Repository\MessageRepository;
use App\Service\MessageIsReadService;
use Doctrine\DBAL\Exception\DeadlockException;

readonly class SystemMessageHandler implements MessageHandlerInterface
{
    public function __construct(
        private MessageRepository $messageRepository,
        private MessageIsReadService $messageIsReadService,
    ) {
    }

    /**
     * @throws DeadlockException
     */
    public function handle(ChatMessageDtoInterface $messageData): MessageHandlerResultDTO
    {
        /** @var Message $message */
        $message = $this->messageRepository->find($messageData->id);
        if (!$message) {
            throw new \RuntimeException(sprintf('Message not found. s%', __METHOD__));
        }

        return $this->messageIsReadService->setMessagesOfTheChatIsRead(
            $message
        );
    }
} 
