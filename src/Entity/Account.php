<?php

namespace App\Entity;

use App\Repository\AccountRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\Ignore;

#[ORM\Entity(repositoryClass: AccountRepository::class)]
#[ORM\Table(name: 'accounts')]
#[ORM\HasLifecycleCallbacks]
class Account
{
    const TYPE_PHYSICAL = 'physical';
    const TYPE_VIRTUAL = 'virtual';
    const TYPE_VIRTUAL_CREDIT = 'virtual_credit';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['account:read', 'operator:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'balances')]
    #[ORM\JoinColumn(nullable: true)] // Nullable for Physical Cash account
    #[Groups(['account:read'])]
    private ?Operator $operator = null;

    #[ORM\Column(length: 20)]
    #[Groups(['account:read', 'account:write', 'operator:read'])]
    private ?string $type = null; // physical | virtual

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2)]
    #[Groups(['account:read', 'account:write', 'operator:read'])]
    private ?string $balance = '0.00';

    #[ORM\Column(length: 10)]
    #[Groups(['account:read', 'account:write', 'operator:read'])]
    private ?string $currency = 'FCFA';

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(['account:read', 'operator:read'])]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['account:read', 'account:write'])]
    private ?string $notes = null;

    public function __construct()
    {
        $this->updatedAt = new \DateTime();
        $this->balance = '0';
        $this->currency = 'FCFA';
    }

    #[ORM\PreUpdate]
    #[ORM\PrePersist]
    public function updateTimestamp(): void
    {
        $this->updatedAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOperator(): ?Operator
    {
        return $this->operator;
    }

    public function setOperator(?Operator $operator): static
    {
        $this->operator = $operator;
        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;
        return $this;
    }

    public function getBalance(): ?string
    {
        return $this->balance;
    }

    public function setBalance(string $balance): static
    {
        $this->balance = $balance;
        return $this;
    }

    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): static
    {
        $this->currency = $currency;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
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
