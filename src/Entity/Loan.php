<?php

namespace App\Entity;

use App\Repository\LoanRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: LoanRepository::class)]
#[ORM\Table(name: 'loans')]
#[ORM\HasLifecycleCallbacks]
class Loan
{
    public const STATUS_PENDING = 'EN_COURS';
    public const STATUS_REPAID = 'REMBOURSÉ';

    public const TYPE_CASH = 'CASH';
    public const TYPE_VIRTUAL = 'VIRTUEL';

    public const DIRECTION_DEBT = 'DETTE'; // L'agent doit au partenaire
    public const DIRECTION_CREDIT = 'CRÉANCE'; // Le partenaire doit à l'agent

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['loan:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['loan:read'])]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: Partner::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['loan:read'])]
    private ?Partner $partner = null;

    #[ORM\Column(length: 20)]
    #[Groups(['loan:read', 'loan:write'])]
    private ?string $direction = self::DIRECTION_DEBT;

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2)]
    #[Groups(['loan:read', 'loan:write'])]
    private ?string $amount = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2)]
    #[Groups(['loan:read', 'loan:write'])]
    private ?string $remainingAmount = null;

    #[ORM\Column(length: 10)]
    #[Groups(['loan:read', 'loan:write'])]
    private ?string $type = self::TYPE_CASH;

    #[ORM\ManyToOne(targetEntity: Operator::class)]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['loan:read'])]
    private ?Operator $operator = null;

    #[ORM\Column(length: 20)]
    #[Groups(['loan:read', 'loan:write'])]
    private ?string $status = self::STATUS_PENDING;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(['loan:read'])]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Groups(['loan:read'])]
    private ?\DateTimeInterface $repaidAt = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['loan:read', 'loan:write'])]
    private ?string $notes = null;

    #[ORM\OneToMany(mappedBy: 'loan', targetEntity: BalanceMovement::class)]
    #[Groups(['loan:read'])]
    private Collection $repayments;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->status = self::STATUS_PENDING;
        $this->repayments = new ArrayCollection();
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

    public function getDirection(): ?string
    {
        return $this->direction;
    }

    public function setDirection(string $direction): static
    {
        $this->direction = $direction;
        return $this;
    }

    public function getAmount(): ?string
    {
        return $this->amount;
    }

    public function setAmount(string $amount): static
    {
        $this->amount = $amount;
        if ($this->remainingAmount === null) {
            $this->remainingAmount = $amount;
        }
        return $this;
    }

    public function getRemainingAmount(): ?string
    {
        return $this->remainingAmount;
    }

    public function setRemainingAmount(string $remainingAmount): static
    {
        $this->remainingAmount = $remainingAmount;
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

    public function getOperator(): ?Operator
    {
        return $this->operator;
    }

    public function setOperator(?Operator $operator): static
    {
        $this->operator = $operator;
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
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

    public function getRepaidAt(): ?\DateTimeInterface
    {
        return $this->repaidAt;
    }

    public function setRepaidAt(?\DateTimeInterface $repaidAt): static
    {
        $this->repaidAt = $repaidAt;
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

    /**
     * @return Collection<int, BalanceMovement>
     */
    public function getRepayments(): Collection
    {
        return $this->repayments;
    }

    public function addRepayment(BalanceMovement $repayment): static
    {
        if (!$this->repayments->contains($repayment)) {
            $this->repayments->add($repayment);
            $repayment->setLoan($this);
        }

        return $this;
    }

    public function removeRepayment(BalanceMovement $repayment): static
    {
        if ($this->repayments->removeElement($repayment)) {
            // set the owning side to null (unless already changed)
            if ($repayment->getLoan() === $this) {
                $repayment->setLoan(null);
            }
        }

        return $this;
    }
}
