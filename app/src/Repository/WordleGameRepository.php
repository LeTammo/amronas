<?php

namespace App\Repository;

use App\Entity\WordleGame;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<WordleGame>
 *
 * @method WordleGame|null find($id, $lockMode = null, $lockVersion = null)
 * @method WordleGame|null findOneBy(array $criteria, array $orderBy = null)
 * @method WordleGame[]    findAll()
 * @method WordleGame[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class WordleGameRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WordleGame::class);
    }

    public function save(WordleGame $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(WordleGame $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findGameWithSolutionAndGuesses($solution, $userId)
    {
        return $this->createQueryBuilder('g')
            ->leftJoin('g.solution', 's')
            ->addSelect('s')
            ->leftJoin('g.guesses', 'guesses')
            ->addSelect('guesses')
            ->where('g.solution = :solution')
            ->andWhere('g.player = :userId')
            ->setParameter('solution', $solution)
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findAllGamesWithGuessesForPlayer($userId)
    {
        return $this->createQueryBuilder('g')
            ->leftJoin('g.guesses', 'guesses')
            ->addSelect('guesses')
            ->where('g.player = :userId')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getResult();
    }

    public function findAllFinishedGamesForPlayer($userId)
    {
        return $this->createQueryBuilder('g')
            ->where('g.player = :userId')
            ->andWhere('g.isFinished = 1')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getResult();
    }
}
