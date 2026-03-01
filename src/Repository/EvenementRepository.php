<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Evenement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Evenement>
 */
class EvenementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Evenement::class);
    }

    /**
     * @return Evenement[]
     */
    public function findAllOrderedByDate(int $limit = 50): array
    {
        return $this->createQueryBuilder('e')
            ->orderBy('e.dateDebut', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Evenement[]
     */
    public function searchOrderedByDate(?string $query, int $limit = 50): array
    {
        return $this->getQuerySearchOrderedByDate($query, $limit)->getResult();
    }

    /** @return \Doctrine\ORM\Query<mixed, Evenement> */
    public function getQuerySearchOrderedByDate(?string $query, int $limit = 50): \Doctrine\ORM\Query
    {
        $qb = $this->createQueryBuilder('e')
            ->orderBy('e.dateDebut', 'DESC')
            ->setMaxResults($limit);

        if ($query !== null && $query !== '') {
            // Match from start: first letter with first letter (e.g. "Par" matches "Paris")
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->like('e.nom', ':q'),
                    $qb->expr()->like('e.description', ':q'),
                    $qb->expr()->like('e.lieu', ':q')
                )
            )->setParameter('q', $query . '%');
        }

        return $qb->getQuery();
    }

    public function save(Evenement $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Evenement $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
