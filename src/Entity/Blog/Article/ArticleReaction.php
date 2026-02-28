<?php

declare(strict_types=1);

namespace App\Entity\Blog\Article;

use App\Repository\Blog\Article\ArticleReactionRepository;
use App\Entity\User;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ArticleReactionRepository::class)]
#[ORM\Table(name: 'article_reaction')]
#[ORM\UniqueConstraint(name: 'uniq_article_user', columns: ['article_id', 'user_id'])]
class ArticleReaction
{
    public const TYPE_LIKE = 'like';
    public const TYPE_DISLIKE = 'dislike';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'reactions')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\ManyToOne(targetEntity: Article::class, inversedBy: 'reactions')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Article $article;

    #[ORM\Column(length: 20)]
    private string $type;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getArticle(): Article
    {
        return $this->article;
    }

    public function setArticle(Article $article): static
    {
        $this->article = $article;
        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        if (!in_array($type, [self::TYPE_LIKE, self::TYPE_DISLIKE], true)) {
            throw new \InvalidArgumentException("Invalid reaction type: $type");
        }
        $this->type = $type;
        return $this;
    }
}
