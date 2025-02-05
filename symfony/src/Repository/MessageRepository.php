<?php

namespace App\Repository;

use App\Entity\Chat;
use App\Entity\Message;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
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

    public function setIsReadByUser(int $userId, int $chatId): void
    {
        $this->createQueryBuilder('m')
            ->update()
            ->set('m.isRead', ':isRead')
            ->where('m.chat = :chatId')
            ->andWhere('m.recipient = :userId')
            ->setParameter('chatId', $chatId)
            ->setParameter('userId', $userId)
            ->setParameter('isRead', true, \PDO::PARAM_BOOL)
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
        // Создаем QueryBuilder
        $qb = $this->createQueryBuilder('m')
            ->where('m.recipient = :recipient')
            ->andWhere('m.sender = :sender')
            ->orderBy('m.id', 'DESC')
            ->setParameter('sender', $chatPartnerId)
            ->setParameter('recipient', $recipient);

        $query = $qb->getQuery()
            // Пагинация
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);

        // Paginator позволяет удобно узнавать общее число элементов и т.д.
        return new Paginator($query, fetchJoinCollection: true);
    }

    public function setMessagesOfChatReadForRecipient(
        Chat $chat,
        User $recipient
    ): void {
        $this->createQueryBuilder('m')
            ->update()
            ->set('m.isRead', ':read')
            ->andWhere('m.chat = :chat')
            ->andWhere('m.recipient = :recipient')
            ->setParameter('read', true)
            ->setParameter('chat', $chat)
            ->setParameter('recipient', $recipient)
            ->getQuery()
            ->execute();
    }
}
