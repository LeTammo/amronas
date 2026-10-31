<?php

namespace App\Repository;

use App\Entity\ListEntry;
use App\Entity\WatchList;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<ListEntry> */
class ListEntryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ListEntry::class);
    }

    /** @return list<ListEntry> all entries with their titles and streaming offers, in one query */
    public function findForList(WatchList $list): array
    {
        return $this->createQueryBuilder('e')
            ->addSelect('t', 'a')
            ->join('e.title', 't')
            ->leftJoin('t.availabilities', 'a')
            ->where('e.list = :list')
            ->setParameter('list', $list)
            ->getQuery()
            ->getResult();
    }
}
