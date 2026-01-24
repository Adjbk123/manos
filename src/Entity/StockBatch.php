<?php

namespace App\Entity;

use App\Repository\StockBatchRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: StockBatchRepository::class)]
#[ORM\Table(name: 'stock_batches')]
class StockBatch
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['stock:read', 'stock_batch:read', 'sale:read', 'stock_arrival:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'stockBatches')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['stock_batch:read', 'stock_arrival:read', 'sale:read'])]
    private ?Product $product = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 2)]
    #[Groups(['stock:read', 'stock_batch:read', 'stock_batch:write', 'stock_arrival:read'])]
    private ?string $purchasePrice = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 2)]
    #[Groups(['stock:read', 'stock_batch:read', 'stock_batch:write', 'stock_arrival:read'])]
    private ?string $minSellingPrice = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 2)]
    #[Groups(['stock:read', 'stock_batch:read', 'stock_batch:write', 'stock_arrival:read'])]
    private ?string $targetSellingPrice = null;

    #[ORM\Column]
    #[Groups(['stock:read', 'stock_batch:read', 'stock_batch:write', 'stock_arrival:read'])]
    private ?int $quantityInitial = null;

    #[ORM\Column]
    #[Groups(['stock:read', 'stock_batch:read', 'stock_arrival:read'])]
    private ?int $quantityRemaining = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(['stock:read', 'stock_batch:read', 'stock_batch:write', 'stock_arrival:read'])]
    private ?\DateTimeInterface $purchaseDate = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['stock:read', 'stock_batch:read', 'stock_batch:write', 'stock_arrival:read'])]
    private ?string $supplier = null;

    #[ORM\ManyToOne(targetEntity: StockArrival::class, inversedBy: 'stockBatches')]
    #[ORM\JoinColumn(nullable: true)]
    private ?StockArrival $stockArrival = null;

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

    public function getPurchasePrice(): ?string
    {
        return $this->purchasePrice;
    }

    public function setPurchasePrice(string $purchasePrice): self
    {
        $this->purchasePrice = $purchasePrice;

        return $this;
    }

    public function getMinSellingPrice(): ?string
    {
        return $this->minSellingPrice;
    }

    public function setMinSellingPrice(string $minSellingPrice): self
    {
        $this->minSellingPrice = $minSellingPrice;

        return $this;
    }

    public function getTargetSellingPrice(): ?string
    {
        return $this->targetSellingPrice;
    }

    public function setTargetSellingPrice(string $targetSellingPrice): self
    {
        $this->targetSellingPrice = $targetSellingPrice;

        return $this;
    }

    public function getQuantityInitial(): ?int
    {
        return $this->quantityInitial;
    }

    public function setQuantityInitial(int $quantityInitial): self
    {
        $this->quantityInitial = $quantityInitial;

        return $this;
    }

    public function getQuantityRemaining(): ?int
    {
        return $this->quantityRemaining;
    }

    public function setQuantityRemaining(int $quantityRemaining): self
    {
        $this->quantityRemaining = $quantityRemaining;

        return $this;
    }

    public function getPurchaseDate(): ?\DateTimeInterface
    {
        return $this->purchaseDate;
    }

    public function setPurchaseDate(\DateTimeInterface $purchaseDate): self
    {
        $this->purchaseDate = $purchaseDate;

        return $this;
    }

    public function getSupplier(): ?string
    {
        return $this->supplier;
    }

    public function setSupplier(?string $supplier): self
    {
        $this->supplier = $supplier;

        return $this;
    }

    public function getStockArrival(): ?StockArrival
    {
        return $this->stockArrival;
    }

    public function setStockArrival(?StockArrival $stockArrival): self
    {
        $this->stockArrival = $stockArrival;

        return $this;
    }
}
