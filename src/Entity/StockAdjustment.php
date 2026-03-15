<?php

namespace App\Entity;

use App\Repository\StockAdjustmentRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: StockAdjustmentRepository::class)]
class StockAdjustment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['stock_adjustment:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['stock_adjustment:read', 'stock_adjustment:write'])]
    private ?Product $product = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['stock_adjustment:read', 'stock_adjustment:write'])]
    private ?StockBatch $batch = null;

    #[ORM\Column]
    #[Groups(['stock_adjustment:read', 'stock_adjustment:write'])]
    private ?int $quantity = null; // Always positive here, the type defines if it's subtraction

    #[ORM\Column(length: 50)]
    #[Groups(['stock_adjustment:read', 'stock_adjustment:write'])]
    private ?string $type = null; // LOSS, DAMAGED, PERSONAL_USE, CORRECTION

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['stock_adjustment:read', 'stock_adjustment:write'])]
    private ?string $reason = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(['stock_adjustment:read'])]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['stock_adjustment:read'])]
    private ?User $createdBy = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(?Product $product): self
    {
        $this->product = $product;
        return $this;
    }

    public function getBatch(): ?StockBatch
    {
        return $this->batch;
    }

    public function setBatch(?StockBatch $batch): self
    {
        $this->batch = $batch;
        return $this;
    }

    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): self
    {
        $this->quantity = $quantity;
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

    public function getReason(): ?string
    {
        return $this->reason;
    }

    public function setReason(?string $reason): self
    {
        $this->reason = $reason;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;
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
