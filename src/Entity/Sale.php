<?php

namespace App\Entity;

use App\Repository\SaleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: SaleRepository::class)]
#[ORM\Table(name: 'sales')]
class Sale
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['sale:read', 'sale:write', 'stock_client:read'])]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(['sale:read', 'sale:write', 'stock_client:read'])]
    private ?\DateTimeInterface $date = null;

    #[ORM\ManyToOne(inversedBy: 'sales')]
    #[Groups(['sale:read', 'sale:write'])]
    private ?StockClient $stockClient = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['sale:read', 'sale:write'])]
    private ?string $clientName = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2)]
    #[Groups(['sale:read', 'sale:write', 'stock_client:read'])]
    private ?string $totalAmount = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2)]
    #[Groups(['sale:read', 'sale:write', 'stock_client:read'])]
    private ?string $paidAmount = null;

    #[ORM\Column(length: 20)]
    #[Groups(['sale:read', 'sale:write', 'stock_client:read'])]
    private ?string $paymentStatus = null; // PAID, PARTIAL, UNPAID

    #[ORM\Column(length: 20)]
    #[Groups(['sale:read', 'sale:write', 'stock_client:read'])]
    private ?string $paymentMethod = null; // CASH, MOMO

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['sale:read'])]
    private ?User $user = null;

    #[ORM\OneToMany(mappedBy: 'sale', targetEntity: SaleItem::class, orphanRemoval: true, cascade: ['persist', 'remove'])]
    #[Groups(['sale:read', 'sale:write'])]
    private Collection $saleItems;

    public function __construct()
    {
        $this->saleItems = new ArrayCollection();
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

    public function getStockClient(): ?StockClient
    {
        return $this->stockClient;
    }

    public function setStockClient(?StockClient $stockClient): self
    {
        $this->stockClient = $stockClient;

        return $this;
    }

    public function getClientName(): ?string
    {
        return $this->clientName;
    }

    public function setClientName(?string $clientName): self
    {
        $this->clientName = $clientName;

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

    public function getPaymentMethod(): ?string
    {
        return $this->paymentMethod;
    }

    public function setPaymentMethod(string $paymentMethod): self
    {
        $this->paymentMethod = $paymentMethod;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return Collection<int, SaleItem>
     */
    public function getSaleItems(): Collection
    {
        return $this->saleItems;
    }

    public function addSaleItem(SaleItem $saleItem): self
    {
        if (!$this->saleItems->contains($saleItem)) {
            $this->saleItems->add($saleItem);
            $saleItem->setSale($this);
        }

        return $this;
    }

    public function removeSaleItem(SaleItem $saleItem): self
    {
        if ($this->saleItems->removeElement($saleItem)) {
            // set the owning side to null (unless already changed)
            if ($saleItem->getSale() === $this) {
                $saleItem->setSale(null);
            }
        }

        return $this;
    }
}
