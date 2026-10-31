<?php

namespace App\Repository;

use App\Entity\ListInvite;
use App\Entity\WatchList;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<ListInvite> */
class ListInviteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ListInvite::class);
    }

    public function findByToken(string $token): ?ListInvite
    {
        return $this->findOneBy(['tokenHash' => ListInvite::hash($token)]);
    }

    /** @return list<ListInvite> */
    public function findOpen(WatchList $list): array
    {
        return $this->createQueryBuilder('i')
            ->where('i.list = :list AND i.usedAt IS NULL AND i.expiresAt > :now')
            ->setParameter('list', $list)
            ->setParameter('now', new \DateTimeImmutable())
            ->orderBy('i.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
