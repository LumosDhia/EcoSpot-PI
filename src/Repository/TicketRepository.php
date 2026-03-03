<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Ticket;
use App\Entity\User;
use App\Enum\ActionDomain;
use App\Enum\TicketStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Ticket>
 */
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
    public function findByUser(User $user, ?TicketStatus $status = null, int $limit = 50): array
    {
        $qb = $this->createQueryBuilder('t')
            ->where('t.user = :user')
            ->setParameter('user', $user->getId(), 'uuid')
            ->orderBy('t.createdAt', 'DESC')
            ->setMaxResults($limit);

        if ($status !== null) {
            $qb->andWhere('t.status = :status')
               ->setParameter('status', $status);
        }

        $query = $qb->getQuery();
        return iterator_to_array(new \Doctrine\ORM\Tools\Pagination\Paginator($query, true), false);
    }

    /** 
     * Pending and sent-back tickets for admin review with search and sort. 
     * @return list<Ticket>
     */
    public function findPendingForAdmin(?string $query = null, string $sortBy = 'newest'): array
    {
        $qb = $this->createQueryBuilder('t')
            ->innerJoin('t.user', 'u')
            ->addSelect('u')
            ->where('t.status IN (:statuses)')
            ->andWhere('u.id IS NOT NULL')
            ->setParameter('statuses', [TicketStatus::PENDING, TicketStatus::SENT_BACK]);

        if ($query) {
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->like('t.title', ':q'),
                    $qb->expr()->like('t.description', ':q'),
                    $qb->expr()->like('u.personName.firstname', ':q'),
                    $qb->expr()->like('u.personName.lastname', ':q')
                )
            )->setParameter('q', '%' . $query . '%');
        }

        switch ($sortBy) {
            case 'oldest':
                $qb->orderBy('t.createdAt', 'ASC');
                break;
            case 'priority_high':
                // Priority enum handling might need mapping if not alphabetical, but let's assume standard for now or use a custom logic if needed.
                // Looking at TicketPriority enum labels/values might be better.
                $qb->orderBy('t.priority', 'DESC'); 
                break;
            case 'priority_low':
                $qb->orderBy('t.priority', 'ASC');
                break;
            case 'newest':
            default:
                $qb->orderBy('t.createdAt', 'DESC');
                break;
        }

        return $qb->getQuery()->getResult();
    }

    /** 
     * All tickets for admin management as a Query. 
     * @return \Doctrine\ORM\Query<int, Ticket>
     */
    public function getQueryAllForAdmin(?string $query = null, string $sortBy = 'newest'): \Doctrine\ORM\Query
    {
        $qb = $this->createQueryBuilder('t')
            ->innerJoin('t.user', 'u')
            ->addSelect('u')
            ->andWhere('u.id IS NOT NULL');

        if ($query) {
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->like('t.title', ':q'),
                    $qb->expr()->like('t.description', ':q'),
                    $qb->expr()->like('u.personName.firstname', ':q'),
                    $qb->expr()->like('u.personName.lastname', ':q')
                )
            )->setParameter('q', '%' . $query . '%');
        }

        switch ($sortBy) {
            case 'oldest':
                $qb->orderBy('t.createdAt', 'ASC');
                break;
            case 'priority_high':
                $qb->orderBy('t.priority', 'DESC'); 
                break;
            case 'priority_low':
                $qb->orderBy('t.priority', 'ASC');
                break;
            case 'status':
                $qb->orderBy('t.status', 'ASC');
                break;
            case 'newest':
            default:
                $qb->orderBy('t.createdAt', 'DESC');
                break;
        }

        /** @var \Doctrine\ORM\Query<int, Ticket> $queryOutput */
        $queryOutput = $qb->getQuery();
        return $queryOutput;
    }

    /** 
     * All tickets for admin management with search and sort. 
     * @return list<Ticket>
     */
    public function findAllForAdmin(?string $query = null, string $sortBy = 'newest'): array
    {
        return array_values($this->getQueryAllForAdmin($query, $sortBy)->getResult());
    }

    /** 
     * Published tickets for public listing. 
     * @return list<Ticket>
     */
    public function findPublished(int $limit = 50): array
    {
        $query = $this->createQueryBuilder('t')
            ->innerJoin('t.user', 'u')
            ->addSelect('u')
            ->where('t.status = :status')
            ->andWhere('u.id IS NOT NULL')
            ->setParameter('status', TicketStatus::PUBLISHED)
            ->orderBy('t.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery();
            
        return iterator_to_array(new \Doctrine\ORM\Tools\Pagination\Paginator($query, true), false);
    }

    /** 
     * Tickets with completion submitted, waiting for admin to mark as achieved. 
     * @return list<Ticket>
     */
    public function findPendingCompletions(int $limit = 50): array
    {
        $query = $this->createQueryBuilder('t')
            ->innerJoin('t.user', 'u')
            ->leftJoin('t.completedBy', 'cb')
            ->addSelect('u', 'cb')
            ->where('t.completionSubmittedAt IS NOT NULL')
            ->andWhere('t.achievedAt IS NULL')
            ->andWhere('u.id IS NOT NULL')
            ->orderBy('t.completionSubmittedAt', 'ASC')
            ->setMaxResults($limit)
            ->getQuery();
            
        return iterator_to_array(new \Doctrine\ORM\Tools\Pagination\Paginator($query, true), false);
    }

    /** 
     * Achieved tickets (for Achievements page). 
     * @return list<Ticket>
     */
    public function findAchieved(int $limit = 50): array
    {
        $query = $this->createQueryBuilder('t')
            ->innerJoin('t.user', 'u')
            ->leftJoin('t.completedBy', 'cb')
            ->addSelect('u', 'cb')
            ->where('t.achievedAt IS NOT NULL')
            ->andWhere('u.id IS NOT NULL')
            ->orderBy('t.achievedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery();
            
        return iterator_to_array(new \Doctrine\ORM\Tools\Pagination\Paginator($query, true), false);
    }

    public function countRecentSpamByUser(User $user, \DateTimeImmutable $since): int
    {
        return (int) $this->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->where('t.user = :user')
            ->andWhere('t.isSpam = :isSpam')
            ->andWhere('t.createdAt >= :since')
            ->setParameter('user', $user->getId(), 'uuid')
            ->setParameter('isSpam', true)
            ->setParameter('since', $since)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countByUserAndStatus(User $user, TicketStatus $status): int
    {
        return $this->count(['user' => $user, 'status' => $status]);
    }
}
