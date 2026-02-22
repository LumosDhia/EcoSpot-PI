<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\ActionDomain;
use App\Enum\TicketPriority;
use App\Enum\TicketStatus;
use App\Repository\TicketRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: TicketRepository::class)]
#[ORM\Table(name: 'ticket')]
#[ORM\Index(columns: ['status'])]
#[ORM\Index(columns: ['user_id'])]
#[ORM\Index(columns: ['created_at'])]
#[ORM\HasLifecycleCallbacks]
class Ticket
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank(message: 'Title is required.')]
    #[Assert\Length(min: 5, max: 255, minMessage: 'Title must be at least 5 characters.')]
    private string $title = '';

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: 'Description is required.')]
    #[Assert\Length(min: 20, minMessage: 'Description must be at least 20 characters.')]
    private string $description = '';

    #[ORM\Column(type: 'string', length: 500)]
    #[Assert\NotBlank(message: 'Location is required.')]
    #[Assert\Length(max: 500)]
    private string $location = '';

    /** Optional picture for the ticket (path under /uploads/tickets/). */
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $image = null;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $latitude = null;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $longitude = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(type: 'string', length: 50, enumType: TicketStatus::class)]
    private TicketStatus $status = TicketStatus::PENDING;

    #[ORM\Column(type: 'string', length: 50, enumType: TicketPriority::class)]
    #[Assert\NotNull(message: 'Priority is required.')]
    private ?TicketPriority $priority = null;

    #[ORM\Column(type: 'string', length: 50, enumType: ActionDomain::class)]
    #[Assert\NotNull(message: 'Domain is required.')]
    private ?ActionDomain $domain = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'tickets')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    /** Admin note when sending back for modification (or refusal reason). */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $adminNotes = null;

    /** User/NGO who submitted completion (proof they did the task). */
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $completedBy = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $completionMessage = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $completionImage = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $completionSubmittedAt = null;

    /** When admin marks the ticket as achieved. */
    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $achievedAt = null;

    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        if ($this->createdAt === null) {
            $this->createdAt = new \DateTimeImmutable();
        }
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    #[ORM\OneToMany(targetEntity: Consigne::class, mappedBy: 'ticket', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private Collection $consignes;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->consignes = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;
        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getLocation(): string
    {
        return $this->location;
    }

    public function setLocation(string $location): static
    {
        $this->location = $location;
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

    public function getLatitude(): ?float
    {
        return $this->latitude;
    }

    public function setLatitude(?float $latitude): static
    {
        $this->latitude = $latitude;
        return $this;
    }

    public function getLongitude(): ?float
    {
        return $this->longitude;
    }

    public function setLongitude(?float $longitude): static
    {
        $this->longitude = $longitude;
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

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function getStatus(): TicketStatus
    {
        return $this->status;
    }

    public function setStatus(TicketStatus $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getPriority(): ?TicketPriority
    {
        return $this->priority;
    }

    public function setPriority(TicketPriority $priority): static
    {
        $this->priority = $priority;
        return $this;
    }

    public function getDomain(): ?ActionDomain
    {
        return $this->domain;
    }

    public function setDomain(ActionDomain $domain): static
    {
        $this->domain = $domain;
        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getAdminNotes(): ?string
    {
        return $this->adminNotes;
    }

    public function setAdminNotes(?string $adminNotes): static
    {
        $this->adminNotes = $adminNotes;
        return $this;
    }

    public function getCompletedBy(): ?User
    {
        return $this->completedBy;
    }

    public function setCompletedBy(?User $completedBy): static
    {
        $this->completedBy = $completedBy;
        return $this;
    }

    public function getCompletionMessage(): ?string
    {
        return $this->completionMessage;
    }

    public function setCompletionMessage(?string $completionMessage): static
    {
        $this->completionMessage = $completionMessage;
        return $this;
    }

    public function getCompletionImage(): ?string
    {
        return $this->completionImage;
    }

    public function setCompletionImage(?string $completionImage): static
    {
        $this->completionImage = $completionImage;
        return $this;
    }

    public function getCompletionSubmittedAt(): ?\DateTimeImmutable
    {
        return $this->completionSubmittedAt;
    }

    public function setCompletionSubmittedAt(?\DateTimeImmutable $completionSubmittedAt): static
    {
        $this->completionSubmittedAt = $completionSubmittedAt;
        return $this;
    }

    public function getAchievedAt(): ?\DateTimeImmutable
    {
        return $this->achievedAt;
    }

    public function setAchievedAt(?\DateTimeImmutable $achievedAt): static
    {
        $this->achievedAt = $achievedAt;
        return $this;
    }

    public function isAchieved(): bool
    {
        return $this->achievedAt !== null;
    }

    public function hasCompletionSubmitted(): bool
    {
        return $this->completionSubmittedAt !== null;
    }

    /**
     * @return Collection<int, Consigne>
     */
    public function getConsignes(): Collection
    {
        return $this->consignes;
    }

    public function addConsigne(Consigne $consigne): static
    {
        if (!$this->consignes->contains($consigne)) {
            $this->consignes->add($consigne);
            $consigne->setTicket($this);
        }

        return $this;
    }

    public function removeConsigne(Consigne $consigne): static
    {
        if ($this->consignes->removeElement($consigne)) {
            // set the owning side to null (unless already changed)
            if ($consigne->getTicket() === $this) {
                $consigne->setTicket(null);
            }
        }

        return $this;
    }
}
