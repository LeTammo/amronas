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
        $entries = $this->findBy(
            ['movieList' => $list->getId()],
            ['timeWatched' => 'ASC']
        );

        $newEntries = [];
        $oldEntries = [];
        foreach ($entries as $entry) {
            if ($entry->getTimeAdded() > new \DateTime("5 minutes ago")) {
                $newEntries[] = $entry;
            } else {
                $oldEntries[] = $entry;
            }
        }

        return array_merge($newEntries, $oldEntries);

        $query = $this->getEntityManager()->createQuery(
            'SELECT e
            FROM App\Entity\MovieListEntry e
            WHERE e.movieListId > :id
            ORDER BY e.timeWatched ASC'
        )->setParameter('id', $list->getId());

        return $query->getResult();
    }

//    /**
//     * @return MovieListEntry[] Returns an array of MovieListEntry objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('m')
//            ->andWhere('m.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('m.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?MovieListEntry
//    {
//        return $this->createQueryBuilder('m')
//            ->andWhere('m.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
