<?php

declare(strict_types=1);

namespace App\Repository\Blog\Article;

use App\Entity\Blog\Article\Article;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Article>
 */
class ArticleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Article::class);
    }

    /**
     * @return Article[]
     */
    public function findPublishedBySearchAndOrder(?string $search, string $order = 'DESC', ?int $categoryId = null, ?int $tagId = null): array
    {
        return $this->getQueryPublishedBySearchAndOrder($search, $order, $categoryId, $tagId)->getResult();
    }

    public function getQueryPublishedBySearchAndOrder(?string $search, string $order = 'DESC', ?int $categoryId = null, ?int $tagId = null): \Doctrine\ORM\Query
    {
        $qb = $this->createQueryBuilder('a')
            ->leftJoin('a.writer', 'w')->addSelect('w')
            ->leftJoin('a.category', 'c')->addSelect('c')
            ->leftJoin('a.tags', 't')->addSelect('t')
            ->andWhere('a.publishedAt IS NOT NULL')
            ->andWhere('a.publishedAt <= :now')
            ->setParameter('now', new \DateTimeImmutable())
            ->orderBy('a.publishedAt', $order === 'ASC' ? 'ASC' : 'DESC');

        if ($search !== null && $search !== '') {
            $qb->andWhere('a.title LIKE :search OR a.content LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        if ($categoryId !== null) {
            $qb->andWhere('c.id = :categoryId')
                ->setParameter('categoryId', $categoryId);
        }

        if ($tagId !== null) {
            $qb->andWhere('t.id = :tagId')
                ->setParameter('tagId', $tagId);
        }

        return $qb->getQuery();
    }

    public function findOnePublishedById(int $id): ?Article
    {
        return $this->createQueryBuilder('a')
            ->leftJoin('a.writer', 'w')->addSelect('w')
            ->andWhere('a.id = :id')
            ->andWhere('a.publishedAt IS NOT NULL')
            ->andWhere('a.publishedAt <= :now')
            ->setParameter('id', $id)
            ->setParameter('now', new \DateTimeImmutable())
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findOnePublishedBySlug(string $slug): ?Article
    {
        return $this->createQueryBuilder('a')
            ->leftJoin('a.writer', 'w')->addSelect('w')
            ->leftJoin('a.category', 'c')->addSelect('c')
            ->leftJoin('a.tags', 't')->addSelect('t')
            ->andWhere('a.slug = :slug')
            ->andWhere('a.publishedAt IS NOT NULL')
            ->andWhere('a.publishedAt <= :now')
            ->setParameter('slug', $slug)
            ->setParameter('now', new \DateTimeImmutable())
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * All articles for admin/NGO (drafts, scheduled, published). Order by createdAt.
     * @return Article[]
     */
    public function findAllForAdmin(string $order = 'DESC'): array
    {
        return $this->createQueryBuilder('a')
            ->leftJoin('a.writer', 'w')->addSelect('w')
            ->orderBy('a.createdAt', $order === 'ASC' ? 'ASC' : 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Articles written by admin (or no writer). For admin's own list: full edit/publish/delete.
     * @return Article[]
     */
    public function findAdminOwnArticles(User $admin, string $order = 'DESC'): array
    {
        return $this->createQueryBuilder('a')
            ->leftJoin('a.writer', 'w')->addSelect('w')
            ->andWhere('a.writer = :admin OR a.writer IS NULL')
            ->setParameter('admin', $admin)
            ->orderBy('a.createdAt', $order === 'ASC' ? 'ASC' : 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Articles written by NGO users. Admin can only delete or return for revision (no edit/publish).
     * @return Article[]
     */
    public function findNgoArticlesForAdmin(string $order = 'DESC'): array
    {
        $all = $this->findAllForAdmin($order);
        return array_values(array_filter($all, fn (Article $a): bool => $a->isWrittenByNgo()));
    }

    public function save(Article $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Article $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
