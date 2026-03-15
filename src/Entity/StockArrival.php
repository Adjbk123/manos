<?php

namespace App\Entity;

use App\Repository\StockArrivalRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: StockArrivalRepository::class)]
class StockArrival
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['stock_arrival:read', 'stock_arrival:write', 'stock:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Supplier::class, inversedBy: 'stockArrivals')]
    #[Groups(['stock_arrival:read', 'stock_arrival:write'])]
    private ?Supplier $supplier = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 2)]
    #[Groups(['stock_arrival:read', 'stock_arrival:write'])]
    private ?string $paidAmount = '0.00';

    #[ORM\Column(length: 20)]
    #[Groups(['stock_arrival:read', 'stock_arrival:write'])]
    private ?string $paymentStatus = 'UNPAID'; // PAID, PARTIAL, UNPAID

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(['stock_arrival:read', 'stock_arrival:write'])]
    private ?\DateTimeInterface $arrivalDate = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 2)]
    #[Groups(['stock_arrival:read', 'stock_arrival:write'])]
    private ?string $totalAmount = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(['stock_arrival:read'])]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\OneToMany(mappedBy: 'stockArrival', targetEntity: StockBatch::class, cascade: ['persist'], orphanRemoval: true)]
    #[Groups(['stock_arrival:read'])]
    private Collection $stockBatches;

    public function __construct()
    {
        $this->stockBatches = new ArrayCollection();
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function setReference(string $reference): self
    {
        $this->reference = $reference;

        return $this;
    }

    public function getSupplier(): ?Supplier
    {
        return $this->supplier;
    }

    public function setSupplier(?Supplier $supplier): self
    {
        $this->supplier = $supplier;
        return $this;
    }

    public function getPaidAmount(): ?string
    {
        return $this->paidAmount;
    }

    public function setPaidAmount(string $paidAmount): self
    {
        $this->paidAmount = $paidAmount;
        return $this;
    }

    public function getPaymentStatus(): ?string
    {
        return $this->paymentStatus;
    }

    public function setPaymentStatus(string $paymentStatus): self
    {
        $this->paymentStatus = $paymentStatus;
        return $this;
    }

    public function getArrivalDate(): ?\DateTimeInterface
    {
        return $this->arrivalDate;
    }

    public function setArrivalDate(\DateTimeInterface $arrivalDate): self
    {
        $this->arrivalDate = $arrivalDate;

        return $this;
    }

    public function getTotalAmount(): ?string
    {
        return $this->totalAmount;
    }

    public function setTotalAmount(string $totalAmount): self
    {
        $this->totalAmount = $totalAmount;

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

    /**
     * @return Collection<int, StockBatch>
     */
    public function getStockBatches(): Collection
    {
        return $this->stockBatches;
    }

    public function addStockBatch(StockBatch $stockBatch): self
    {
        if (!$this->stockBatches->contains($stockBatch)) {
            $this->stockBatches->add($stockBatch);
            $stockBatch->setStockArrival($this);
        }

        return $this;
    }

    public function removeStockBatch(StockBatch $stockBatch): self
    {
        if ($this->stockBatches->removeElement($stockBatch)) {
            // set the owning side to null (unless already changed)
            if ($stockBatch->getStockArrival() === $this) {
                $stockBatch->setStockArrival(null);
            }
        }

        return $this;
    }
}
