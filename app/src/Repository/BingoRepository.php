<?php

namespace App\Repository;

use App\Entity\Bingo;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Bingo>
 *
 * @method Bingo|null find($id, $lockMode = null, $lockVersion = null)
 * @method Bingo|null findOneBy(array $criteria, array $orderBy = null)
 * @method Bingo[]    findAll()
 * @method Bingo[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BingoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Bingo::class);
    }

    public function save(Bingo $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Bingo $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

//    /**
//     * @return Bingo[] Returns an array of Bingo objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('b')
//            ->andWhere('b.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('b.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Bingo
//    {
//        return $this->createQueryBuilder('b')
//            ->andWhere('b.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }

    /**
     * @param User|null $user
     * @return Bingo|null
     */
    public function findLast(User $user = null): ?Bingo
    {
        if ($user instanceof User) {
            $this->createQueryBuilder('b')
                ->andWhere('b.player = :user')
                ->setParameter('user', $user);
        }

        return $this->createQueryBuilder('b')
            ->orderBy('b.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
}
