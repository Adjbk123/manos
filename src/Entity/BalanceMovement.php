<?php

namespace App\Entity;

use App\Repository\BalanceMovementRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: BalanceMovementRepository::class)]
#[ORM\Table(name: 'balance_movements')]
#[ORM\HasLifecycleCallbacks]
class BalanceMovement
{
    const TYPE_TRANSACTION = 'transaction';
    const TYPE_APPRO = 'appro';
    const TYPE_ADJUST = 'adjust';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['balance_movement:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Account::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['balance_movement:read'])]
    private ?Account $account = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['balance_movement:read'])]
    private ?User $user = null;

    #[ORM\Column(length: 20)]
    #[Groups(['balance_movement:read'])]
    private ?string $type = null; // transaction, appro, adjust

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2)]
    #[Groups(['balance_movement:read'])]
    private ?string $amount = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2)]
    #[Groups(['balance_movement:read'])]
    private ?string $beforeBalance = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2)]
    #[Groups(['balance_movement:read'])]
    private ?string $afterBalance = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(['balance_movement:read'])]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\ManyToOne(targetEntity: Transaction::class)]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['balance_movement:read'])]
    private ?Transaction $transaction = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['balance_movement:read'])]
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

    public function getAccount(): ?Account
    {
        return $this->account;
    }

    public function setAccount(?Account $account): static
    {
        $this->account = $account;
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

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;
        return $this;
    }

    public function getAmount(): ?string
    {
        return $this->amount;
    }

    public function setAmount(string $amount): static
    {
        $this->amount = $amount;
        return $this;
    }

    public function getBeforeBalance(): ?string
    {
        return $this->beforeBalance;
    }

    public function setBeforeBalance(string $beforeBalance): static
    {
        $this->beforeBalance = $beforeBalance;
        return $this;
    }

    public function getAfterBalance(): ?string
    {
        return $this->afterBalance;
    }

    public function setAfterBalance(string $afterBalance): static
    {
        $this->afterBalance = $afterBalance;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getTransaction(): ?Transaction
    {
        return $this->transaction;
    }

    public function setTransaction(?Transaction $transaction): static
    {
        $this->transaction = $transaction;
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
