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
    $conn = $this->getEntityManager()->getConnection();

    $sql = "
SELECT s.* FROM wordle_solution s
JOIN wordle_game g ON s.id = g.solution_id
JOIN wordle_guess r ON g.id = r.game_id
WHERE g.player_id = :userId AND g.is_finished = 0;
        ORDER BY RAND()
        LIMIT 1;
    ";

    $stmt = $conn->executeQuery($sql, ['userId' => $userId]); 

    return $stmt->fetch();
}

public function findRandomUnattemptedSolvedGame(int $userId)
{
    $conn = $this->getEntityManager()->getConnection();

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
GROUP BY s.id;
        ORDER BY RAND()
        LIMIT 1;
    ";

    $stmt = $conn->executeQuery($sql, ['userId' => $userId]);

    return $stmt->fetch();
}


public function findRandomUnsolvedOrSolvedByOtherUser(int $userId)
{
    $conn = $this->getEntityManager()->getConnection();

    $sql = "
	SELECT s.* FROM wordle_solution s
        LEFT JOIN wordle_game g ON s.id = g.solution_id
        WHERE (g.player_id IS NULL or (g.player_id = :userId AND g.is_finished = 0))
        ORDER BY RAND()
        LIMIT 1;
    ";

    $stmt = $conn->executeQuery($sql, ['userId' => $userId]); 

    return $stmt->fetch();
}
}
