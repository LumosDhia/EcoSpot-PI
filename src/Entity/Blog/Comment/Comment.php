<?php

declare(strict_types=1);

namespace App\Entity\Blog\Comment;

use App\Repository\Blog\Comment\CommentRepository;
use App\Entity\Blog\Article\Article;
use App\Entity\User;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CommentRepository::class)]
#[ORM\Table(name: 'comment')]
#[ORM\Index(columns: ['article_id'])]
class Comment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: "Author is required.")]
    #[Assert\Length(min: 2, max: 100)]
    private string $author;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: 'The comment cannot be empty.')]
    #[Assert\Length(min: 5, max: 2000, minMessage: 'The comment must be at least {{ limit }} characters.', maxMessage: 'The comment cannot exceed {{ limit }} characters.')]
    #[Assert\Regex(pattern: '/(?:.*[a-zA-Z]){5,}/s', message: 'The comment must contain at least 5 letters.')]
    private string $content;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\ManyToOne(targetEntity: Article::class, inversedBy: 'comments')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull(message: "Article is required.")]
    private Article $article;

    /** Logged-in user who wrote the comment. */
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $authorUser;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    private bool $flagged = false;

    public function __construct(?User $authorUser = null)
    {
        $this->createdAt = new \DateTimeImmutable();
        if ($authorUser) {
            $this->authorUser = $authorUser;
        }
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAuthor(): string
    {
        return $this->author;
    }

    public function setAuthor(string $author): static
    {
        $this->author = $author;

        return $this;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): static
    {
        $this->content = $content;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
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

    public function isFlagged(): bool
    {
        return $this->flagged;
    }

    public function getFlagged(): bool
    {
        return $this->flagged;
    }

    public function setFlagged(bool $flagged): static
    {
        $this->flagged = $flagged;
        return $this;
    }

    public function getAuthorUser(): User
    {
        return $this->authorUser;
    }

}
