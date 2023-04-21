<?php

namespace App\Repository;

use App\Entity\MovieList;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @extends ServiceEntityRepository<MovieList>
 *
 * @method MovieList|null find($id, $lockMode = null, $lockVersion = null)
 * @method MovieList|null findOneBy(array $criteria, array $orderBy = null)
 * @method MovieList[]    findAll()
 * @method MovieList[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MovieListRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MovieList::class);
    }

    public function save(MovieList $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(MovieList $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @param User $maintainer
     * @return MovieList[]
     */
    public function findByMaintainer(UserInterface $maintainer): array
    {
        return $this->createQueryBuilder('ml')
            ->join('ml.maintainer', 'm')
            ->where('m.id = :maintainerId')
            ->setParameter('maintainerId', $maintainer->getId())
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @param User $subscriber
     * @return MovieList[]
     */
    public function findBySubscriber(UserInterface $subscriber): array
    {
        return $this->createQueryBuilder('ml')
            ->join('ml.subscriber', 's')
            ->where('s.id = :subscriberId')
            ->setParameter('subscriberId', $subscriber->getId())
            ->getQuery()
            ->getResult()
        ;
    }

//    public function findOneBySomeField($value): ?MovieList
//    {
//        return $this->createQueryBuilder('m')
//            ->andWhere('m.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
