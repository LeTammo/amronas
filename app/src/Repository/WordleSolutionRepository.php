<?php

namespace App\Repository;

use App\Entity\WordleSolution;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<WordleSolution>
 *
 * @method WordleSolution|null find($id, $lockMode = null, $lockVersion = null)
 * @method WordleSolution|null findOneBy(array $criteria, array $orderBy = null)
 * @method WordleSolution[]    findAll()
 * @method WordleSolution[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class WordleSolutionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WordleSolution::class);
    }

    public function save(WordleSolution $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(WordleSolution $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
