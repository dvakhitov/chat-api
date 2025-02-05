<?php

namespace App\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\DTO\Api\History\Message\ChatContentDTO;
use App\DTO\Api\History\Message\ChatDTO;
use App\DTO\Api\History\Message\ChatPartnerDTO;
use App\DTO\Api\History\Message\LastMessageDTO;
use App\Entity\Message;
use App\Entity\User;
use App\Event\HistoryRequestedEvent;
use App\Repository\MessageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

readonly class ChatHistoryProvider implements ProviderInterface
{
    public function __construct(
        private MessageRepository $messageRepository,
        private Security $security,
        private EntityManagerInterface $em,
        private EventDispatcherInterface $dispatcher,
    ) {
    }

    /**
     * Метод, который вызывается Api Platform
     * при запросе GET /chat/message/history
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ChatDTO
    {
        $request = $context['request'];
        $queryParams = $request->query->all();

        /** @var User $user */
        $user = $this->security->getUser();
        // 1. Выбираем нужные сообщения
        // Возможно, вы захотите использовать query-параметры для фильтрации
        // или брать ID конкретного чата/пользователя из $uriVariables.
        // Здесь просто возьмём все:
        /** @var Message[] $messages */
        $messages = $this->messageRepository->findMessagesOfChatForRecipient(
            recipient: $user,
            chatPartnerId: $uriVariables['chatPartnerId'],
            page: $queryParams['page'] ?? 1,
            limit: $queryParams['size'] ?? 50,
        );

        $chat = null;
        foreach ($messages as $message) {
            if (is_null($chat)) {
                $chat = $message->getChat();
            }
            $message->setIsRead(true);
        }
        $this->em->flush();


        // 2. Создаём корневой DTO
        $chatDTO = new ChatDTO();
        $chatDTO->content = [];

        // 3. Наполняем DTO данными
        foreach ($messages as $message) {
            $chatContentDTO = new ChatContentDTO();

            // ---- chatPartner (на выбор sender или recipient) ----
            $messageRecipient = $message->getRecipient();
            $chatPartnerDTO = new ChatPartnerDTO();
            $chatPartnerDTO->id = $messageRecipient->getId();
            $chatPartnerDTO->email = $messageRecipient->getEmail();
            $chatPartnerDTO->firstName = $messageRecipient->getFirstName();
            $chatPartnerDTO->lastName = $messageRecipient->getLastName();
            $chatPartnerDTO->photoUrl = $messageRecipient->getPhotoUrl();
            $chatPartnerDTO->createdDate = ($messageRecipient->getCreatedAt()) ? $messageRecipient->getCreatedAt()->format('Y-m-d H:i:s') : '';
            $chatPartnerDTO->emailVerified = (bool)$messageRecipient->getIsEmailVerified();

            $chatContentDTO->chatPartner = $chatPartnerDTO;

            // ---- lastMessage ----
            $lastMessageDTO = new LastMessageDTO();
            $lastMessageDTO->id = $message->getId();
            $lastMessageDTO->senderId = $message->getSender()?->getId() ?? 0;
            $lastMessageDTO->createdDate = $message->getCreatedAt()?->format(DATE_ATOM) ?? '';
            $lastMessageDTO->updatedDate = $message->getUpdatedAt()?->format(DATE_ATOM) ?? '';
            $lastMessageDTO->content = $message->getContent() ?? '';
            $lastMessageDTO->status = $message->getStatus();

            $chatContentDTO->lastMessage = $lastMessageDTO;

            // ---- кол-во непрочитанных (примерная логика) ----
            $chatContentDTO->numberUnreadMessages = $message->isRead() ? 0 : 1;

            // ---- некий timestamp (пример) ----
            $chatContentDTO->numberUnreadTimeStamp = time() * 1000;

            $chatDTO->content[] = $chatContentDTO;
        }

        $this->dispatcher->dispatch(new HistoryRequestedEvent($chat, $user));

        return $chatDTO;
    }
}
