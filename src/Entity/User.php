<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Blog\Article\Article;
use App\Entity\Blog\Article\ArticleReaction;
use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[UniqueEntity(fields: ['email'], message: 'There is already an account with this email')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    private ?string $email = null;

    /** @var list<string> */
    #[ORM\Column]
    private array $roles = [];

    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(length: 100)]
    private ?string $lastname = null;

    #[ORM\Column(length: 100)]
    private ?string $firstname = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $address = null;

    #[ORM\Column(length: 5, nullable: true)]
    private ?string $zipcode = null;

    #[ORM\Column(length: 150, nullable: true)]
    private ?string $city = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    /** @var \Doctrine\Common\Collections\Collection<int, \App\Entity\Ticket> */
    #[ORM\OneToMany(targetEntity: Ticket::class, mappedBy: 'user', orphanRemoval: true)]
    private \Doctrine\Common\Collections\Collection $tickets;

    /** @var \Doctrine\Common\Collections\Collection<int, Article> */
    #[ORM\OneToMany(targetEntity: Article::class, mappedBy: 'writer')]
    private \Doctrine\Common\Collections\Collection $articles;

    /** @var \Doctrine\Common\Collections\Collection<int, ArticleReaction> */
    #[ORM\OneToMany(targetEntity: ArticleReaction::class, mappedBy: 'user', orphanRemoval: true)]
    private \Doctrine\Common\Collections\Collection $reactions;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->tickets = new \Doctrine\Common\Collections\ArrayCollection();
        $this->articles = new \Doctrine\Common\Collections\ArrayCollection();
        $this->reactions = new \Doctrine\Common\Collections\ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /** @return list<string> */
    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';
        return array_unique($roles);
    }

    /** @param list<string> $roles */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;
        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;
        return $this;
    }

    public function eraseCredentials(): void
    {
    }

    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    public function setLastname(string $lastname): static
    {
        $this->lastname = $lastname;
        return $this;
    }

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function setFirstname(string $firstname): static
    {
        $this->firstname = $firstname;
        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): static
    {
        $this->address = $address;
        return $this;
    }

    public function getZipcode(): ?string
    {
        return $this->zipcode;
    }

    public function setZipcode(?string $zipcode): static
    {
        $this->zipcode = $zipcode;
        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(?string $city): static
    {
        $this->city = $city;
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

    /** @return \Doctrine\Common\Collections\Collection<int, Ticket> */
    public function getTickets(): \Doctrine\Common\Collections\Collection
    {
        return $this->tickets;
    }

    public function addTicket(Ticket $ticket): static
    {
        if (!$this->tickets->contains($ticket)) {
            $this->tickets->add($ticket);
            $ticket->setUser($this);
        }
        return $this;
    }

    public function removeTicket(Ticket $ticket): static
    {
        if ($this->tickets->removeElement($ticket) && $ticket->getUser() === $this) {
            $ticket->setUser(null);
        }
        return $this;
    }

    /** @return \Doctrine\Common\Collections\Collection<int, Article> */
    public function getArticles(): \Doctrine\Common\Collections\Collection
    {
        return $this->articles;
    }

    /** @return \Doctrine\Common\Collections\Collection<int, ArticleReaction> */
    public function getReactions(): \Doctrine\Common\Collections\Collection
    {
        return $this->reactions;
    }
}
