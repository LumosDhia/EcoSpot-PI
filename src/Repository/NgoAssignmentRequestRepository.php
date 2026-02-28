<?php

namespace App\Repository;

use App\Entity\NgoAssignmentRequest;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<NgoAssignmentRequest>
 *
 * @method NgoAssignmentRequest|null find($id, $lockMode = null, $lockVersion = null)
 * @method NgoAssignmentRequest|null findOneBy(array<string, mixed> $criteria, array<string, string>|null $orderBy = null)
 * @method NgoAssignmentRequest[]    findAll()
 * @method NgoAssignmentRequest[]    findBy(array<string, mixed> $criteria, array<string, string>|null $orderBy = null, $limit = null, $offset = null)
 */
class NgoAssignmentRequestRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NgoAssignmentRequest::class);
    }
    
    public function save(NgoAssignmentRequest $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(NgoAssignmentRequest $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
