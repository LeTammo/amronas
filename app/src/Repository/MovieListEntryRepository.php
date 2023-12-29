<?php

namespace App\Repository;

use App\Entity\MovieList;
use App\Entity\MovieListEntry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MovieListEntry>
 *
 * @method MovieListEntry|null find($id, $lockMode = null, $lockVersion = null)
 * @method MovieListEntry|null findOneBy(array $criteria, array $orderBy = null)
 * @method MovieListEntry[]    findAll()
 * @method MovieListEntry[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MovieListEntryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MovieListEntry::class);
    }

    public function save(MovieListEntry $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(MovieListEntry $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findByCustomOrder(MovieList $list): array
    {
        $qb = $this->createQueryBuilder('e')
            ->leftJoin('e.movie', 'm')
            ->addSelect('m')
            ->where('e.movieList = :list')
            ->setParameter('list', $list)
            ->addOrderBy('e.timeWatched', 'ASC');

        $entries = $qb->getQuery()->getResult();

        $fiveMinutesAgo = new \DateTime("5 minutes ago");

        usort($entries, function ($a, $b) use ($fiveMinutesAgo) {
            $aIsNew = $a->getTimeAdded() > $fiveMinutesAgo;
            $bIsNew = $b->getTimeAdded() > $fiveMinutesAgo;

            if ($aIsNew && !$bIsNew) {
                return -1;
            } elseif (!$aIsNew && $bIsNew) {
                return 1;
            }

            return 0;
        });

        return $entries;
    }
}
