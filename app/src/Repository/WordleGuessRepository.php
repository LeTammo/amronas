<?php

namespace App\Repository;

use App\Entity\WordleGuess;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<WordleGuess>
 *
 * @method WordleGuess|null find($id, $lockMode = null, $lockVersion = null)
 * @method WordleGuess|null findOneBy(array $criteria, array $orderBy = null)
 * @method WordleGuess[]    findAll()
 * @method WordleGuess[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class WordleGuessRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WordleGuess::class);
    }

    public function save(WordleGuess $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(WordleGuess $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
