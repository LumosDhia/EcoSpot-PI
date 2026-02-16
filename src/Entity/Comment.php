<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\CommentRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\User;
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
    private ?string $author = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: 'The comment cannot be empty.')]
    #[Assert\Length(min: 5, max: 2000, minMessage: 'The comment must be at least {{ limit }} characters.', maxMessage: 'The comment cannot exceed {{ limit }} characters.')]
    private ?string $content = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\ManyToOne(targetEntity: Article::class, inversedBy: 'comments')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull(message: "Article is required.")]
    private ?Article $article = null;

    /** Logged-in user who wrote the comment (optional; author string used for display). */
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $authorUser = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    private bool $flagged = false;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAuthor(): ?string
    {
        return $this->author;
    }

    public function setAuthor(string $author): static
    {
        $this->author = $author;

        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): static
    {
        $this->content = $content;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getArticle(): ?Article
    {
        return $this->article;
    }

    public function setArticle(?Article $article): static
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

    public function getAuthorUser(): ?User
    {
        return $this->authorUser;
    }

    public function setAuthorUser(?User $authorUser): static
    {
        $this->authorUser = $authorUser;
        return $this;
    }
}
