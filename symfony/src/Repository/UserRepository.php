<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function findOneByChatUuid(string $uuid): ?User
    {
        return $this->findOneBy(['chatUserUuid' => Uuid::fromString($uuid)]);
    }

    public function findByIds(array $ids)
    {
        $qb = $this->createQueryBuilder('u')
            ->andWhere('u.id IN (:ids)')
            ->setParameter('ids', $ids);

        // Отладочная информация
        $query = $qb->getQuery();

        return $query->getResult();
    }
}
