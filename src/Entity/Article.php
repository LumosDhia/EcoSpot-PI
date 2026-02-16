<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ArticleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ArticleRepository::class)]
#[ORM\Table(name: 'article')]
#[ORM\Index(columns: ['created_at'])]
#[ORM\Index(columns: ['published_at'])]
class Article
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Title is required.')]
    #[Assert\Length(min: 5, max: 100, minMessage: 'Title must be at least {{ limit }} characters.', maxMessage: 'Title cannot exceed {{ limit }} characters.')]
    #[Assert\Regex(pattern: '/^\d+$/', match: false, message: 'The title cannot be composed entirely of numbers.')]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: 'Content is required.')]
    #[Assert\Length(min: 20, minMessage: 'The article content must be more detailed (at least {{ limit }} characters).')]
    private ?string $content = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Url(message: 'The image URL is not valid.')]
    private ?string $image = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $publishedAt = null;

    /** When admin returns an NGO article for revision (unpublishes and adds this note). */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $adminRevisionNote = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $writer = null;

    /**
     * @var Collection<int, Comment>
     */
    #[ORM\OneToMany(targetEntity: Comment::class, mappedBy: 'article', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $comments;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->comments = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

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

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): static
    {
        $this->image = $image;

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

    public function getWriter(): ?User
    {
        return $this->writer;
    }

    public function setWriter(?User $writer): static
    {
        $this->writer = $writer;
        return $this;
    }

    public function getPublishedAt(): ?\DateTimeImmutable
    {
        return $this->publishedAt;
    }

    public function setPublishedAt(?\DateTimeImmutable $publishedAt): static
    {
        $this->publishedAt = $publishedAt;
        return $this;
    }

    public function isPublished(): bool
    {
        if ($this->publishedAt === null) {
            return false;
        }
        return $this->publishedAt <= new \DateTimeImmutable();
    }

    /** Draft, scheduled (future), or published (past). */
    public function getPublicationStatus(): string
    {
        if ($this->publishedAt === null) {
            return 'draft';
        }
        return $this->publishedAt > new \DateTimeImmutable() ? 'scheduled' : 'published';
    }

    public function getAdminRevisionNote(): ?string
    {
        return $this->adminRevisionNote;
    }

    public function setAdminRevisionNote(?string $adminRevisionNote): static
    {
        $this->adminRevisionNote = $adminRevisionNote;
        return $this;
    }

    public function isWrittenByNgo(): bool
    {
        $writer = $this->getWriter();
        if ($writer === null) {
            return false;
        }
        return \in_array('ROLE_NGO', $writer->getRoles(), true);
    }

    /**
     * @return Collection<int, Comment>
     */
    public function getComments(): Collection
    {
        return $this->comments;
    }

    public function addComment(Comment $comment): static
    {
        if (!$this->comments->contains($comment)) {
            $this->comments->add($comment);
            $comment->setArticle($this);
        }

        return $this;
    }

    public function removeComment(Comment $comment): static
    {
        if ($this->comments->removeElement($comment)) {
            if ($comment->getArticle() === $this) {
                $comment->setArticle(null);
            }
        }

        return $this;
    }
}
