<?php

namespace App\Entity;

use App\Repository\CessionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: CessionRepository::class)]
#[ORM\Table(name: 'cessions')]
#[ORM\HasLifecycleCallbacks]
class Cession
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['cession:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['cession:read'])]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: Partner::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['cession:read'])]
    private ?Partner $partner = null;

    #[ORM\ManyToOne(targetEntity: Operator::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['cession:read'])]
    private ?Operator $operatorSource = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2)]
    #[Groups(['cession:read', 'cession:write'])]
    private ?string $amountCeded = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2)]
    #[Groups(['cession:read', 'cession:write'])]
    private ?string $amountReceived = null;

    #[ORM\Column(length: 20)]
    #[Groups(['cession:read', 'cession:write'])]
    private ?string $typeReceived = 'CASH'; // CASH or VIRTUEL

    #[ORM\ManyToOne(targetEntity: Operator::class)]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['cession:read'])]
    private ?Operator $operatorReceived = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(['cession:read'])]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['cession:read', 'cession:write'])]
    private ?string $notes = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getPartner(): ?Partner
    {
        return $this->partner;
    }

    public function setPartner(?Partner $partner): static
    {
        $this->partner = $partner;
        return $this;
    }

    public function getOperatorSource(): ?Operator
    {
        return $this->operatorSource;
    }

    public function setOperatorSource(?Operator $operator): static
    {
        $this->operatorSource = $operator;
        return $this;
    }

    public function getAmountCeded(): ?string
    {
        return $this->amountCeded;
    }

    public function setAmountCeded(string $amount): static
    {
        $this->amountCeded = $amount;
        return $this;
    }

    public function getAmountReceived(): ?string
    {
        return $this->amountReceived;
    }

    public function setAmountReceived(string $amount): static
    {
        $this->amountReceived = $amount;
        return $this;
    }

    public function getTypeReceived(): ?string
    {
        return $this->typeReceived;
    }

    public function setTypeReceived(string $type): static
    {
        $this->typeReceived = $type;
        return $this;
    }

    public function getOperatorReceived(): ?Operator
    {
        return $this->operatorReceived;
    }

    public function setOperatorReceived(?Operator $operator): static
    {
        $this->operatorReceived = $operator;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): static
    {
        $this->notes = $notes;
        return $this;
    }
}
