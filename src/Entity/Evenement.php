<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\EvenementRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: EvenementRepository::class)]
#[ORM\Table(name: 'event')]
#[ORM\Index(columns: ['started_at'])]
#[UniqueEntity(fields: ['nom'], message: 'An event with this name already exists.')]
#[Assert\Callback(callback: 'validateDatesNotInPast')]
class Evenement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(name: 'name', length: 255, unique: true)]
    #[Assert\NotBlank(message: 'Event name is required.')]
    #[Assert\Length(min: 3, minMessage: 'Name must be at least 3 characters.')]
    private string $nom;

    #[Gedmo\Slug(fields: ['nom'])]
    #[ORM\Column(length: 128, unique: true)]
    private string $slug;

    #[ORM\Column(type: 'text')]
    #[Assert\NotBlank(message: 'Description is required.')]
    #[Assert\Length(min: 10, minMessage: 'Description must be at least 10 characters.')]
    private string $description;

    #[ORM\Column(name: 'capacity')]
    #[Assert\NotNull(message: 'Capacity is required.')]
    #[Assert\Positive(message: 'Capacity must be a positive number.')]
    private int $capacite;

    #[ORM\Column(name: 'location', length: 255)]
    #[Assert\NotBlank(message: 'Location is required.')]
    private string $lieu;

    #[ORM\Column(name: 'started_at', type: 'datetime', nullable: true)]
    #[Assert\NotBlank(message: 'Start date is required.')]
    #[Assert\Type(\DateTimeInterface::class)]
    private ?\DateTimeInterface $dateDebut = null;

    #[ORM\Column(name: 'ended_at', type: 'datetime', nullable: true)]
    #[Assert\NotBlank(message: 'End date is required.')]
    #[Assert\Type(\DateTimeInterface::class)]
    #[Assert\GreaterThan(propertyPath: 'dateDebut', message: 'End date must be after the start date.')]
    private ?\DateTimeInterface $dateFin = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image = null;

    /**
     * @var Collection<int, Sponsor>
     */
    #[ORM\ManyToMany(targetEntity: Sponsor::class, inversedBy: 'evenements')]
    #[ORM\JoinTable(name: 'event_sponsor', joinColumns: [new ORM\JoinColumn(name: 'event_id', referencedColumnName: 'id', onDelete: 'CASCADE')], inverseJoinColumns: [new ORM\JoinColumn(name: 'sponsor_id', referencedColumnName: 'id', onDelete: 'CASCADE')])]
    private Collection $sponsors;

    /**
     * @var Collection<int, User>
     */
    #[ORM\ManyToMany(targetEntity: User::class)]
    #[ORM\JoinTable(name: 'event_participation')]
    private Collection $participants;


    #[ORM\Embedded(class: \App\Entity\Embeddable\Coordinates::class, columnPrefix: false)]
    private \App\Entity\Embeddable\Coordinates $coordinates;

    public function __construct()
    {
        $this->sponsors = new ArrayCollection();
        $this->participants = new ArrayCollection();
        $this->coordinates = new \App\Entity\Embeddable\Coordinates();
    }


    public function validateDatesNotInPast(ExecutionContextInterface $context): void
    {
        $today = new \DateTimeImmutable('today');
        if ($this->dateDebut !== null && $this->dateDebut < $today) {
            $context->buildViolation('Start date cannot be in the past.')
                ->atPath('dateDebut')
                ->addViolation();
        }
        if ($this->dateFin !== null && $this->dateFin < $today) {
            $context->buildViolation('End date cannot be in the past.')
                ->atPath('dateFin')
                ->addViolation();
        }
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;
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

    public function getCapacite(): int
    {
        return $this->capacite;
    }

    public function setCapacite(int $capacite): static
    {
        $this->capacite = $capacite;
        return $this;
    }

    public function getLieu(): string
    {
        return $this->lieu;
    }

    public function setLieu(string $lieu): static
    {
        $this->lieu = $lieu;
        return $this;
    }

    public function getDateDebut(): ?\DateTimeInterface
    {
        return $this->dateDebut;
    }

    public function setDateDebut(?\DateTimeInterface $dateDebut): static
    {
        $this->dateDebut = $dateDebut;
        return $this;
    }

    public function getDateFin(): ?\DateTimeInterface
    {
        return $this->dateFin;
    }

    public function setDateFin(?\DateTimeInterface $dateFin): static
    {
        $this->dateFin = $dateFin;
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

    /**
     * @return Collection<int, Sponsor>
     */
    public function getSponsors(): Collection
    {
        return $this->sponsors;
    }

    public function addSponsor(Sponsor $sponsor): static
    {
        if (!$this->sponsors->contains($sponsor)) {
            $this->sponsors->add($sponsor);
            $sponsor->addEvenement($this);
        }
        return $this;
    }

    public function removeSponsor(Sponsor $sponsor): static
    {
        if ($this->sponsors->removeElement($sponsor)) {
            $sponsor->removeEvenement($this);
        }
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

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;
        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getParticipants(): Collection
    {
        return $this->participants;
    }

    public function addParticipant(User $user): static
    {
        if (!$this->participants->contains($user)) {
            $this->participants->add($user);
        }
        return $this;
    }

    public function removeParticipant(User $user): static
    {
        $this->participants->removeElement($user);
        return $this;
    }
}

