<?php

namespace App\Repository;

use App\Entity\Chat;
use App\Entity\User;
use App\Entity\ChatPartner;
use App\Helper\IntegersToIndex;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;

class ChatRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Chat::class);
    }

    public function findOrCreatePrivateChat(User $user1, User $user2): Chat
    {
        $chatIndex = IntegersToIndex::convert([$user1->getId(), $user2->getId()]);
        $qb = $this->createQueryBuilder('c')
            ->andWhere('c.type = :type')
            ->andWhere('c.chatIndex = :chatIndex')
            ->setParameter('type', 'private')
            ->setParameter('chatIndex', $chatIndex)
            ;

        $chat = $qb->getQuery()->getOneOrNullResult();

        try {
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
        } catch (NonUniqueResultException $e) {
            return $this->findOrCreatePrivateChat($user1, $user2);
        }

        return $chat;
    }

    public function getUnreadMessagesChatsCount(int|User $user): int
    {
        return $this->createQueryBuilder('c')
            ->select('COUNT(DISTINCT c.id)')
            ->join('c.messages', 'm')
            ->where('m.recipient = :user')
            ->andWhere('m.isRead = false')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getCountUnreadMessagesByChatPartner(int $chatPartnerId, int $chatId)
    {
        return $this->createQueryBuilder('c')
            ->select('COUNT(m.id)') // DISTINCT не нужен, так как m.id уникален
            ->join('c.messages', 'm')
            ->join('m.sender', 'u') // Присоединяем отправителя сообщения (User)
            ->join('c.chatPartners', 'cp') // Присоединяем чат-партнеров
            ->where('c.id = :chatId')
            ->andWhere('u.id != cp.user') // Сообщение отправлено НЕ текущим пользователем
            ->andWhere('cp.id = :chatPartnerId') // Фильтруем партнера
            ->andWhere('m.isRead = false') // Только непрочитанные сообщения
            ->setParameter('chatId', $chatId)
            ->setParameter('chatPartnerId', $chatPartnerId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findAllChatsByUser(User $user, int $page = 1, int $limit = 20): array
    {
        return $this->createQueryBuilder('c')
            // Соединяем с ChatPartner
            ->join('c.chatPartners', 'cp')
            ->where('cp.user = :user')
            ->setParameter('user', $user)

            // Сортируем по lastMessage.id
            ->orderBy('c.lastMessage', 'DESC')

            // Пагинация, если нужно
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)

            ->getQuery()
            ->getResult();
    }
}
