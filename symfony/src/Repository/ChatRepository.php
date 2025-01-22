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
            ->setParameters([
                'type' => 'private',
                'user1' => $user1,
                'user2' => $user2
            ]);

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

            $this->getEntityManager()->persist($chat);
            $this->getEntityManager()->persist($partner1);
            $this->getEntityManager()->persist($partner2);
            $this->getEntityManager()->flush();
        }

        return $chat;
    }
}
