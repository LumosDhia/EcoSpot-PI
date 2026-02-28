<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ConsigneRepository;
use Doctrine\DBAL\Types\Types;
use App\Entity\Trait\BlameableTrait;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ConsigneRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Consigne
{
    use BlameableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: 'La description de la consigne ne peut pas être vide.')]
    private string $description;

    #[ORM\Column]
    private bool $isCompleted = false;

    #[ORM\Column]
    private int $position = 0;

    #[ORM\ManyToOne(targetEntity: Ticket::class, inversedBy: 'consignes')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Ticket $ticket;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(length: 20, options: ['default' => 'MEDIUM'])]
    private string $difficulty = 'MEDIUM';

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->difficulty = 'MEDIUM';
    }

    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function isCompleted(): bool
    {
        return $this->isCompleted;
    }

    public function setIsCompleted(bool $isCompleted): static
    {
        $this->isCompleted = $isCompleted;

        return $this;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): static
    {
        $this->position = $position;

        return $this;
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

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getDifficulty(): \App\Enum\TaskDifficulty
    {
        return \App\Enum\TaskDifficulty::from($this->difficulty);
    }

    public function setDifficulty(\App\Enum\TaskDifficulty|string $difficulty): static
    {
        $this->difficulty = $difficulty instanceof \App\Enum\TaskDifficulty ? $difficulty->value : $difficulty;

        return $this;
    }
}
