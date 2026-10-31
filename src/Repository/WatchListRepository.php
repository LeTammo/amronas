<?php

namespace App\Repository;

use App\Entity\User;
use App\Entity\WatchList;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<WatchList> */
class WatchListRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WatchList::class);
    }

    /** @return list<WatchList> lists the user owns or is a member of */
    public function findForUser(User $user): array
    {
        return $this->createQueryBuilder('l')
            ->leftJoin('l.members', 'm')
            ->where('l.owner = :user OR m.user = :user')
            ->setParameter('user', $user)
            ->orderBy('l.createdAt')
            ->getQuery()
            ->getResult();
    }
}
