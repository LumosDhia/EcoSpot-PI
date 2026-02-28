<?php

declare(strict_types=1);

namespace App\Repository\Blog\Article;

use App\Entity\Blog\Article\ArticleReaction;
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

    public function findOneByArticleAndUser(int $articleId, \Symfony\Component\Uid\UuidV7 $userId): ?ArticleReaction
    {
        return $this->findOneBy([
            'article' => $articleId,
            'user' => $userId
        ]);
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
