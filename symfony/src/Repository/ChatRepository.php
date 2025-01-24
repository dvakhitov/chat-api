<?php

namespace App\Repository;

use App\Entity\Chat;
use App\Entity\User;
use App\Entity\ChatPartner;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ChatRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Chat::class);
    }

    public function findOrCreatePrivateChat(User $user1, User $user2): Chat
    {
        $qb = $this->createQueryBuilder('c')
            ->join('c.chatPartners', 'cp1')
            ->join('c.chatPartners', 'cp2')
            ->where('c.type = :type')
            ->andWhere('cp1.user = :user1')
            ->andWhere('cp2.user = :user2')
            ->setParameter('type', 'private')
            ->setParameter('user1', $user1)
            ->setParameter('user2', $user2)
        ;

        $chat = $qb->getQuery()->getOneOrNullResult();

        if (!$chat) {
            $chat = new Chat();
            $chat->setType('private');

            $partner1 = new ChatPartner();
            $partner1->setChat($chat);
            $partner1->setUser($user1);

            $partner2 = new ChatPartner();
            $partner2->setChat($chat);
            $partner2->setUser($user2);

            $chat->setChatPartners([$partner1, $partner2]);
            $this->getEntityManager()->persist($chat);
            $this->getEntityManager()->persist($partner1);
            $this->getEntityManager()->persist($partner2);
            $this->getEntityManager()->flush();
        }

        return $chat;
    }

    public function getUnreadMessagesChats(ChatPartner $chatPartner): int
    {
        return $this->createQueryBuilder('c')
            ->select('COUNT(DISTINCT c.id)')
            ->join('c.chatPartners', 'cp')
            ->join('c.messages', 'm')
            ->andWhere('cp.id = :chatPartner')
            ->andWhere('m.isRead = :false')
            ->setParameter('chatPartner', $chatPartner)
            ->setParameter('false', false)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
