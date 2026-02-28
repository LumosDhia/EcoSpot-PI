<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\NgoAssignmentRequestRepository;
use App\Entity\Trait\BlameableTrait;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: NgoAssignmentRequestRepository::class)]
#[ORM\HasLifecycleCallbacks]
class NgoAssignmentRequest
{
    use BlameableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Ticket::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Ticket $ticket;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $ngo;

    #[ORM\Column(length: 50)]
    private string $status = 'PENDING';

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        if (!isset($this->createdAt)) {
            $this->createdAt = new \DateTimeImmutable();
        }
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTicket(): Ticket
    {
        return $this->ticket;
    }

    public function setTicket(Ticket $ticket): static
    {
        $this->ticket = $ticket;
        return $this;
    }

    public function getNgo(): User
    {
        return $this->ngo;
    }

    public function setNgo(User $ngo): static
    {
        $this->ngo = $ngo;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
