<?php

namespace App\Entity;

use App\Repository\ShopCashMovementRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: ShopCashMovementRepository::class)]
class ShopCashMovement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['shop_cash:read'])]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(['shop_cash:read'])]
    private ?\DateTimeInterface $date = null;

    #[ORM\Column(length: 10)]
    #[Groups(['shop_cash:read'])]
    private ?string $type = null; // IN, OUT

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2)]
    #[Groups(['shop_cash:read'])]
    private ?string $amount = null;

    #[ORM\Column(length: 255)]
    #[Groups(['shop_cash:read'])]
    private ?string $label = null;

    #[ORM\Column(length: 50)]
    #[Groups(['shop_cash:read'])]
    private ?string $source = null; // SALE, SUPPLIER_PAYMENT, EXPENSE, MANUAL

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    #[Groups(['shop_cash:read'])]
    private ?int $sourceId = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['shop_cash:read'])]
    private ?User $createdBy = null;

    public function __construct()
    {
        $this->date = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): self
    {
        $this->date = $date;
        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function getAmount(): ?string
    {
        return $this->amount;
    }

    public function setAmount(string $amount): self
    {
        $this->amount = $amount;
        return $this;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(string $label): self
    {
        $this->label = $label;
        return $this;
    }

    public function getSource(): ?string
    {
        return $this->source;
    }

    public function setSource(string $source): self
    {
        $this->source = $source;
        return $this;
    }

    public function getSourceId(): ?int
    {
        return $this->sourceId;
    }

    public function setSourceId(?int $sourceId): self
    {
        $this->sourceId = $sourceId;
        return $this;
    }

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?User $createdBy): self
    {
        $this->createdBy = $createdBy;
        return $this;
    }
}
