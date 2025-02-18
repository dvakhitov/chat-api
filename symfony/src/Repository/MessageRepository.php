<?php

namespace App\Repository;

use App\Entity\Chat;
use App\Entity\Message;
use App\Entity\User;
use App\Helper\IntegersToIndex;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Exception\DeadlockException;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Tools\Pagination\Paginator;

class MessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Message::class);
    }

    public function countUnreadChatsForRecipient(int $recipientId)
    {
        return $this->createQueryBuilder('m')
            ->select('COUNT(DISTINCT m.chat)')
            ->where('m.recipient = :recipientId')
            ->andWhere('m.isRead = false')
            ->setParameter('recipientId', $recipientId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @param int $userId
     * @param int $chatId
     * @return void
     */
    public function setIsReadByUser(int $userId, int $chatId): void
    {
        $this->createQueryBuilder('m')
            ->update()
            ->set('m.isRead', ':isRead')
            ->where('m.chat = :chatId')
            ->andWhere('m.recipient = :userId')
            ->andWhere('m.isRead = false')
            ->setParameter('chatId', $chatId)
            ->setParameter('isRead', true)
            ->setParameter('userId', $userId)
            ->getQuery()
            ->execute();
    }

    /**
     * Возвращает непрочитанные сообщения для конкретного пользователя,
     * у которых поле chat == null (не привязаны к чату).
     * Вывод упорядочен по id DESC.
     * Пагинация (offset + limit).
     */
    public function findMessagesOfChatForRecipient(
        User $recipient,
        int $chatPartnerId,
        int $page = 1,
        int $limit = 20
    ): Paginator {
        $chatIndex = IntegersToIndex::convert([$recipient->getId(), $chatPartnerId]);
        $qb = $this->createQueryBuilder('m')
            ->andWhere('m.chatIndex = :chatIndex')
            ->setParameter('chatIndex', $chatIndex)
            ->orderBy('m.id', 'DESC');

        $query = $qb->getQuery()
            // Пагинация
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);

        // Paginator позволяет удобно узнавать общее число элементов и т.д.
        return new Paginator($query, fetchJoinCollection: true);
    }

    public function getLastUnreadMessage(Chat $chat, int $partnerId): ?Message
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.chat = :chat')
            ->andWhere('m.isRead = false')
            ->andWhere('m.recipient != :userId')
            ->setParameter('chat', $chat)
            ->setParameter('userId', $partnerId)
            ->orderBy('m.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function countUnreadMessagesOfTheChatFoUser(Chat $chat, User $user): int
    {
        return $this->createQueryBuilder('m')
            ->select('COUNT(DISTINCT m.id)')
            ->andWhere('m.chat = :chat')
            ->andWhere('m.isRead = false')
            ->andWhere('m.recipient = :user')
            ->setParameter('chat', $chat)
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getLastMessageOfChatByUserId(Chat $chat, int $userId)
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.chat = :chat')
            ->andWhere('m.recipient = :userId')
            ->setParameter('chat', $chat)
            ->setParameter('userId', $userId)
            ->orderBy('m.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
