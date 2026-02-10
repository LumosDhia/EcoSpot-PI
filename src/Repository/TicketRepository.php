<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Ticket;
use App\Entity\User;
use App\Enum\ActionDomain;
use App\Enum\TicketStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class TicketRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Ticket::class);
    }

    public function save(Ticket $ticket, bool $flush = true): void
    {
        $this->getEntityManager()->persist($ticket);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Ticket $ticket, bool $flush = true): void
    {
        $this->getEntityManager()->remove($ticket);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /** @return list<Ticket> */
    public function findByUser(User $user, ?TicketStatus $status = null): array
    {
        $qb = $this->createQueryBuilder('t')
            ->where('t.user = :user')
            ->setParameter('user', $user)
            ->orderBy('t.createdAt', 'DESC');

        if ($status !== null) {
            $qb->andWhere('t.status = :status')->setParameter('status', $status);
        }

        return $qb->getQuery()->getResult();
    }

    /** Pending and sent-back tickets for admin review. */
    public function findPendingForAdmin(): array
    {
        return $this->createQueryBuilder('t')
            ->innerJoin('t.user', 'u')
            ->addSelect('u')
            ->where('t.status IN (:statuses)')
            ->setParameter('statuses', [TicketStatus::PENDING, TicketStatus::SENT_BACK])
            ->orderBy('t.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /** Published tickets for public listing. */
    public function findPublished(): array
    {
        return $this->createQueryBuilder('t')
            ->innerJoin('t.user', 'u')
            ->addSelect('u')
            ->where('t.status = :status')
            ->setParameter('status', TicketStatus::PUBLISHED)
            ->orderBy('t.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /** Tickets with completion submitted, waiting for admin to mark as achieved. */
    public function findPendingCompletions(): array
    {
        return $this->createQueryBuilder('t')
            ->innerJoin('t.user', 'u')
            ->leftJoin('t.completedBy', 'cb')
            ->addSelect('u', 'cb')
            ->where('t.completionSubmittedAt IS NOT NULL')
            ->andWhere('t.achievedAt IS NULL')
            ->orderBy('t.completionSubmittedAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /** Achieved tickets (for Achievements page). */
    public function findAchieved(): array
    {
        return $this->createQueryBuilder('t')
            ->innerJoin('t.user', 'u')
            ->leftJoin('t.completedBy', 'cb')
            ->addSelect('u', 'cb')
            ->where('t.achievedAt IS NOT NULL')
            ->orderBy('t.achievedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
