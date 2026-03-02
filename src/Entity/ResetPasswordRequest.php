<?php

namespace App\Entity;

use App\Repository\ResetPasswordRequestRepository;
use Doctrine\ORM\Mapping as ORM;
use SymfonyCasts\Bundle\ResetPassword\Model\ResetPasswordRequestInterface;
use SymfonyCasts\Bundle\ResetPassword\Model\ResetPasswordRequestTrait;
use Symfony\Component\Serializer\Annotation\Ignore;
use SensitiveParameter;

#[ORM\Entity(repositoryClass: ResetPasswordRequestRepository::class)]
class ResetPasswordRequest implements ResetPasswordRequestInterface
{
    use ResetPasswordRequestTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    /** @var string */
    #[Ignore]
    #[ORM\Column(type: 'string', length: 100)]
    protected $hashedToken;

    public function __construct(User $user, \DateTimeInterface $expiresAt, string $selector, #[SensitiveParameter] string $hashedToken)
    {
        $this->user = $user;
        $this->initialize($expiresAt, $selector, $hashedToken);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setHashedToken(#[SensitiveParameter] string $hashedToken): self
    {
        $this->hashedToken = $hashedToken;
        return $this;
    }

    #[Ignore]
    public function getHashedToken(): string
    {
        return $this->hashedToken;
    }

    public function getUser(): User
    {
        /** @var User */
        return $this->user;
    }
}
