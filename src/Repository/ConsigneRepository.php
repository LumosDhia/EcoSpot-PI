<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Consigne;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Consigne>
 */
class ConsigneRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Consigne::class);
    }

    /**
     * @return Consigne[] Returns an array of Consigne objects ordered by position
     */
    public function findByTicketOrdered(int $ticketId): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.ticket = :ticketId')
            ->setParameter('ticketId', $ticketId)
            ->orderBy('c.position', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
