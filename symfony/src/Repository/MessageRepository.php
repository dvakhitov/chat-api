<?php

namespace App\Repository;

use App\Entity\Chat;
use App\Entity\Message;
use App\Entity\User;
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
     * @throws DeadlockException
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
        // Создаем QueryBuilder
        $qb = $this->createQueryBuilder('m')
            ->where('(m.sender = :recipient AND m.recipient = :sender) OR (m.sender = :sender AND m.recipient = :recipient)')
            ->orderBy('m.id', 'DESC')
            ->setParameter('recipient', $recipient)
            ->setParameter('sender', $chatPartnerId);

        $query = $qb->getQuery()
            // Пагинация
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);

        // Paginator позволяет удобно узнавать общее число элементов и т.д.
        return new Paginator($query, fetchJoinCollection: true);
    }
}
