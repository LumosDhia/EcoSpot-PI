<?php

declare(strict_types=1);

namespace App\Entity\Blog\Article;

use App\Repository\Blog\Article\ArticleRepository;
use App\Entity\Blog\Comment\Comment;
use App\Entity\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\Blog\Article\ArticleReaction;
use App\Entity\Blog\Article\Category;
use App\Entity\Blog\Article\Tag;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[UniqueEntity(fields: ['title'], message: 'An article with this title already exists.')]
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

    #[ORM\Column(length: 255, unique: true)]
    #[Assert\NotBlank(message: 'Title is required.')]
    #[Assert\Length(min: 5, max: 100, minMessage: 'Title must be at least {{ limit }} characters.', maxMessage: 'Title cannot exceed {{ limit }} characters.')]
    #[Assert\Regex(pattern: '/(?:.*[a-zA-Z]){5,}/s', message: 'The title must contain at least 5 letters.')]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: 'Content is required.')]
    #[Assert\Length(min: 20, minMessage: 'The article content must be more detailed (at least {{ limit }} characters).')]
    #[Assert\Regex(pattern: '/(?:.*[a-zA-Z]){5,}/s', message: 'The article content must contain at least 5 letters.')]
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

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'articles')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $writer = null;

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0])]
    private int $views = 0;

    #[Gedmo\Slug(fields: ['title'])]
    #[ORM\Column(length: 128, unique: true)]
    private ?string $slug = null;

    #[ORM\ManyToOne(targetEntity: Category::class, inversedBy: 'articles')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Category $category = null;

    /**
     * @var Collection<int, Tag>
     */
    #[ORM\ManyToMany(targetEntity: Tag::class, inversedBy: 'articles')]
    #[ORM\JoinTable(name: 'article_tag')]
    private Collection $tags;

    /**
     * @var Collection<int, Comment>
     */
    #[ORM\OneToMany(targetEntity: Comment::class, mappedBy: 'article', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $comments;

    /**
     * @var Collection<int, ArticleReaction>
     */
    #[ORM\OneToMany(targetEntity: ArticleReaction::class, mappedBy: 'article', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $reactions;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $seoTitle = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $seoDescription = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $seoKeywords = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->comments = new ArrayCollection();
        $this->tags = new ArrayCollection();
        $this->reactions = new ArrayCollection();
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

    public function getReadingTime(): int
    {
        $text = strip_tags($this->content);
        $wordCount = str_word_count($text);
        return (int) ceil($wordCount / 200) ?: 1;
    }

    public function getViews(): int
    {
        return $this->views;
    }

    public function setViews(int $views): static
    {
        $this->views = $views;
        return $this;
    }

    public function incrementViews(): static
    {
        $this->views++;
        return $this;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): static
    {
        $this->category = $category;
        return $this;
    }

    /**
     * @return Collection<int, Tag>
     */
    public function getTags(): Collection
    {
        return $this->tags;
    }

    public function addTag(Tag $tag): static
    {
        if (!$this->tags->contains($tag)) {
            $this->tags->add($tag);
        }
        return $this;
    }

    public function removeTag(Tag $tag): static
    {
        $this->tags->removeElement($tag);
        return $this;
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

    /**
     * @return Collection<int, ArticleReaction>
     */
    public function getReactions(): Collection
    {
        return $this->reactions;
    }

    public function getLikesCount(): int
    {
        return $this->reactions->filter(fn(ArticleReaction $r) => $r->getType() === ArticleReaction::TYPE_LIKE)->count();
    }

    public function getDislikesCount(): int
    {
        return $this->reactions->filter(fn(ArticleReaction $r) => $r->getType() === ArticleReaction::TYPE_DISLIKE)->count();
    }

    public function getUserReaction(User $user): ?ArticleReaction
    {
        foreach ($this->reactions as $reaction) {
            if ($reaction->getUser() === $user) {
                return $reaction;
            }
        }
        return null;
    }

    public function getSeoTitle(): ?string
    {
        return $this->seoTitle;
    }

    public function setSeoTitle(?string $seoTitle): static
    {
        $this->seoTitle = $seoTitle;
        return $this;
    }

    public function getSeoDescription(): ?string
    {
        return $this->seoDescription;
    }

    public function setSeoDescription(?string $seoDescription): static
    {
        $this->seoDescription = $seoDescription;
        return $this;
    }

    public function getSeoKeywords(): ?string
    {
        return $this->seoKeywords;
    }

    public function setSeoKeywords(?string $seoKeywords): static
    {
        $this->seoKeywords = $seoKeywords;
        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;
        return $this;
    }
}
