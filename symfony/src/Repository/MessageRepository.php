<?php

namespace App\Repository;

use App\Entity\Message;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

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

    public function setIsReadByUser(int $userId, int $chatId)
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
}
