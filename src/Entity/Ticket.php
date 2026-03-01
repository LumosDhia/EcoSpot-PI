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
use App\Entity\Trait\BlameableTrait;
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
    use BlameableTrait;

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

    #[ORM\Embedded(class: \App\Entity\Embeddable\Coordinates::class, columnPrefix: false)]
    private \App\Entity\Embeddable\Coordinates $coordinates;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(length: 50)]
    private string $status = 'PENDING';

    #[ORM\Column(length: 50)]
    #[Assert\NotNull(message: 'Priority is required.')]
    private string $priority;

    #[ORM\Column(length: 50)]
    #[Assert\NotNull(message: 'Domain is required.')]
    private string $domain;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'tickets')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    /** Admin note when sending back for modification (or refusal reason). */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $adminNotes = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $assignedNgo = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $ngoNotes = null;

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



    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    /** @var Collection<int, Consigne> */
    #[ORM\OneToMany(targetEntity: Consigne::class, mappedBy: 'ticket', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private Collection $consignes;

    #[ORM\Column]
    private bool $isSpam = false;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->consignes = new ArrayCollection();
        $this->coordinates = new \App\Entity\Embeddable\Coordinates();
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
        return $this->coordinates->getLatitude();
    }

    public function setLatitude(?float $latitude): static
    {
        $this->coordinates->setLatitude($latitude);
        return $this;
    }

    public function getLongitude(): ?float
    {
        return $this->coordinates->getLongitude();
    }

    public function setLongitude(?float $longitude): static
    {
        $this->coordinates->setLongitude($longitude);
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getStatus(): TicketStatus
    {
        return TicketStatus::from($this->status);
    }

    public function setStatus(TicketStatus|string $status): static
    {
        $this->status = $status instanceof TicketStatus ? $status->value : $status;
        return $this;
    }

    public function getPriority(): TicketPriority
    {
        return TicketPriority::from($this->priority);
    }

    public function setPriority(TicketPriority|string $priority): static
    {
        $this->priority = $priority instanceof TicketPriority ? $priority->value : $priority;
        return $this;
    }

    public function getDomain(): ActionDomain
    {
        return ActionDomain::from($this->domain);
    }

    public function setDomain(ActionDomain|string $domain): static
    {
        $this->domain = $domain instanceof ActionDomain ? $domain->value : $domain;
        return $this;
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

    public function getAdminNotes(): ?string
    {
        return $this->adminNotes;
    }

    public function setAdminNotes(?string $adminNotes): static
    {
        $this->adminNotes = $adminNotes;
        return $this;
    }

    public function getAssignedNgo(): ?User
    {
        return $this->assignedNgo;
    }

    public function setAssignedNgo(?User $assignedNgo): static
    {
        $this->assignedNgo = $assignedNgo;
        return $this;
    }

    public function getNgoNotes(): ?string
    {
        return $this->ngoNotes;
    }

    public function setNgoNotes(?string $ngoNotes): static
    {
        $this->ngoNotes = $ngoNotes;
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

    public function submitCompletion(User $user, ?string $message, ?string $image): void
    {
        $this->completedBy = $user;
        $this->completionMessage = $message;
        $this->completionImage = $image;
        $this->completionSubmittedAt = new \DateTimeImmutable();
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

    public function getAchievedAt(): ?\DateTimeImmutable
    {
        return $this->achievedAt;
    }

    protected function setAchievedAt(?\DateTimeImmutable $achievedAt): static
    {
        $this->achievedAt = $achievedAt;
        return $this;
    }

    public function markAsAchieved(): void
    {
        $this->achievedAt = new \DateTimeImmutable();
        $this->setStatus(TicketStatus::COMPLETED);
    }

    public function isAchieved(): bool
    {
        return $this->achievedAt !== null;
    }

    public function hasCompletionSubmitted(): bool
    {
        return $this->completionSubmittedAt !== null;
    }

    protected function setCompletionSubmittedAt(?\DateTimeImmutable $completionSubmittedAt): static
    {
        $this->completionSubmittedAt = $completionSubmittedAt;
        return $this;
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
                // ticket is mandatory, removing from collection usually implies deletion
            }
        }

        return $this;
    }

    public function isSpam(): bool
    {
        return $this->isSpam;
    }

    public function setIsSpam(bool $isSpam): static
    {
        $this->isSpam = $isSpam;

        return $this;
    }
}
