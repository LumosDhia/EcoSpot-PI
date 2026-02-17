<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\ArticleReaction;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ArticleReaction>
 */
class ArticleReactionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ArticleReaction::class);
    }

    public function findOneByArticleAndUser(int $articleId, int $userId): ?ArticleReaction
    {
        return $this->createQueryBuilder('ar')
            ->andWhere('ar.article = :articleId')
            ->andWhere('ar.user = :userId')
            ->setParameter('articleId', $articleId)
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function save(ArticleReaction $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(ArticleReaction $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
