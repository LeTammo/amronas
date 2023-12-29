<?php

namespace App\Repository;

use App\Entity\BingoTemplate;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<BingoTemplate>
 *
 * @method BingoTemplate|null find($id, $lockMode = null, $lockVersion = null)
 * @method BingoTemplate|null findOneBy(array $criteria, array $orderBy = null)
 * @method BingoTemplate[]    findAll()
 * @method BingoTemplate[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BingoTemplateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BingoTemplate::class);
    }

    public function save(BingoTemplate $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(BingoTemplate $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
