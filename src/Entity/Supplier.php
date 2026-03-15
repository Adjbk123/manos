<?php

namespace App\Entity;

use App\Repository\SupplierRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: SupplierRepository::class)]
class Supplier
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['supplier:read', 'stock_arrival:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['supplier:read', 'supplier:write', 'stock_arrival:read'])]
    private ?string $name = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['supplier:read', 'supplier:write'])]
    private ?string $phone = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['supplier:read', 'supplier:write'])]
    private ?string $address = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2)]
    #[Groups(['supplier:read'])]
    private ?string $balance = '0.00'; // Positive means we owe money

    #[ORM\OneToMany(mappedBy: 'supplier', targetEntity: StockArrival::class)]
    private Collection $stockArrivals;

    public function __construct()
    {
        $this->stockArrivals = new ArrayCollection();
        $this->balance = '0.00';
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): self
    {
        $this->phone = $phone;
        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): self
    {
        $this->address = $address;
        return $this;
    }

    public function getBalance(): ?string
    {
        return $this->balance;
    }

    public function setBalance(string $balance): self
    {
        $this->balance = $balance;
        return $this;
    }

    /**
     * @return Collection<int, StockArrival>
     */
    public function getStockArrivals(): Collection
    {
        return $this->stockArrivals;
    }

    public function addStockArrival(StockArrival $stockArrival): self
    {
        if (!$this->stockArrivals->contains($stockArrival)) {
            $this->stockArrivals->add($stockArrival);
            $stockArrival->setSupplier($this);
        }
        return $this;
    }

    public function removeStockArrival(StockArrival $stockArrival): self
    {
        if ($this->stockArrivals->removeElement($stockArrival)) {
            // set the owning side to null (unless already changed)
            if ($stockArrival->getSupplier() === $this) {
                $stockArrival->setSupplier(null);
            }
        }
        return $this;
    }
}
