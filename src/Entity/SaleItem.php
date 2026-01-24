<?php

namespace App\Entity;

use App\Repository\SaleItemRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: SaleItemRepository::class)]
#[ORM\Table(name: 'sale_items')]
class SaleItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['sale:read', 'sale:write'])]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'saleItems')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Sale $sale = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['sale:read', 'sale:write'])]
    private ?StockBatch $stockBatch = null;

    #[ORM\Column]
    #[Groups(['sale:read', 'sale:write'])]
    private ?int $quantity = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 2)]
    #[Groups(['sale:read', 'sale:write'])]
    private ?string $unitSellingPrice = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 2)]
    #[Groups(['sale:read'])]
    private ?string $unitPurchasePrice = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 2, nullable: true)]
    #[Groups(['sale:read'])]
    private ?string $profit = null;

    public function __construct()
    {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSale(): ?Sale
    {
        return $this->sale;
    }

    public function setSale(?Sale $sale): self
    {
        $this->sale = $sale;

        return $this;
    }

    public function getStockBatch(): ?StockBatch
    {
        return $this->stockBatch;
    }

    public function setStockBatch(?StockBatch $stockBatch): self
    {
        $this->stockBatch = $stockBatch;

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

    public function getUnitSellingPrice(): ?string
    {
        return $this->unitSellingPrice;
    }

    public function setUnitSellingPrice(string $unitSellingPrice): self
    {
        $this->unitSellingPrice = $unitSellingPrice;

        return $this;
    }

    public function getUnitPurchasePrice(): ?string
    {
        return $this->unitPurchasePrice;
    }

    public function setUnitPurchasePrice(string $unitPurchasePrice): self
    {
        $this->unitPurchasePrice = $unitPurchasePrice;

        return $this;
    }

    public function getProfit(): ?string
    {
        return $this->profit;
    }

    public function setProfit(?string $profit): self
    {
        $this->profit = $profit;

        return $this;
    }
}
