<?php

namespace App\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\DTO\Api\History\Message\MessagesHistoryDTO;
use App\Entity\Chat;
use App\Entity\User;
use App\Event\HistoryRequestedEvent;
use App\Factory\History\MessagesHistoryDTOFactory;
use App\Repository\MessageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

readonly class MessagesHistoryProvider implements ProviderInterface
{
    function __construct(
        private MessageRepository $messageRepository,
        private Security $security,
        private EntityManagerInterface $em,
        private EventDispatcherInterface $dispatcher,
        private MessagesHistoryDTOFactory $messagesHistoryDTOFactory,
        private LoggerInterface $logger,
    ) {
    }

    function provide(Operation $operation, array $uriVariables = [], array $context = []): MessagesHistoryDTO
    {
        $request = $context['request'];
        $queryParams = $request->query->all();

        /** @var User $user */
        $user = $this->security->getUser();

        $messagesPaginator = $this->messageRepository->findMessagesOfChatForRecipient(
            recipient: $user,
            chatPartnerId: $uriVariables['chatPartnerId'],
            page: $queryParams['page'] ?? 1,
            limit: $queryParams['size'] ?? 50,
        );

        $chat = null;
        foreach ($messagesPaginator as $message) {
            if (is_null($chat)) {
                $chat = $message->getChat();
            }
            $message->setIsRead(true);
        }
        $this->em->flush();

        $messageHistoryDTO = $this->messagesHistoryDTOFactory->create($messagesPaginator->getIterator()->getArrayCopy(), $user);

        if (empty($messageHistoryDTO->content)) {
            return $messageHistoryDTO;
        }
        $this->dispatcher->dispatch(new HistoryRequestedEvent($chat, $this->getChatPartnerUser($chat, $user)));

        return $messageHistoryDTO;
    }

    private function getChatPartnerUser(Chat $chat, User $user): User
    {
        foreach ($chat->getChatPartners() as $chatPartner) {
            if ($chatPartner->getUser()->getId() !== $user->getId()) {
                return $chatPartner->getUser();
            }
        }

        $this->logger->error('Chat partner not found - chatId: ' . $chat->getId() . ' userId: ' . $user->getId() . __METHOD__);
        throw new \RuntimeException('Chat partner not found');
    }
}
