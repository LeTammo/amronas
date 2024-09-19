<?php

namespace App\Repository;

use App\Entity\User;
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

    public function findRandomStartedGame(int $userId)
    {
        return $this->createQueryBuilder('s')
            ->join('s.games', 'g')
            ->join('g.guesses', 'r')
            ->where('g.player = :userId')
            ->andWhere('g.isFinished = false')
            ->orderBy('s.createdAt', 'ASC')
            ->setMaxResults(1)
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findRandomUnattemptedSolvedGame(int $userId)
    {
        $sql = "
            SELECT s.*
            FROM wordle_solution s
            JOIN wordle_game g ON s.id = g.solution_id
            JOIN wordle_guess r ON g.id = r.game_id
            WHERE g.is_solved = 1
            AND g.player_id <> :userId
            AND s.id NOT IN (
                SELECT g2.solution_id
                FROM wordle_game g2
                JOIN wordle_guess r2 ON g2.id = r2.game_id
                WHERE g2.player_id = :userId
            )
            LIMIT 1;
        ";

        return $this->getOneOrNullResult(
            $this->getEntityManager()->getConnection()->executeQuery($sql, ['userId' => $userId])->fetch()
        );
    }


    public function findRandomGameThatHasNotBeenStarted(int $userId)
    {
        return $this->createQueryBuilder('s')
            ->leftJoin('s.games', 'g')
            ->where('g.player IS NULL')
            ->orWhere('g.player = :userId')
            ->andWhere('g.isFinished = 0')
            ->orderBy('s.createdAt', 'ASC')
            ->setMaxResults(1)
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    private function getOneOrNullResult($result)
    {
        if ($result === false) {
            return null;
        }

        return $this->getEntityManager()->getRepository(WordleSolution::class)->find($result['id']);
    }
}
