<?php

namespace App\Entity;

use App\Repository\SaleZoneRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: SaleZoneRepository::class)]
#[ORM\Table(name: 'sale_zones')]
class SaleZone
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['sale_zone:read', 'product:read', 'sale:read', 'pos:read', 'stock:read', 'stock_arrival:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['sale_zone:read', 'sale_zone:write', 'product:read', 'sale:read', 'pos:read', 'stock:read', 'stock_arrival:read'])]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['sale_zone:read', 'sale_zone:write'])]
    private ?string $description = null;

    #[ORM\Column]
    #[Groups(['sale_zone:read', 'sale_zone:write', 'pos:read'])]
    private bool $isActive = true;

    /**
     * @var Collection<int, ProductPrice>
     */
    #[ORM\OneToMany(mappedBy: 'saleZone', targetEntity: ProductPrice::class, orphanRemoval: true)]
    private Collection $productPrices;

    public function __construct()
    {
        $this->productPrices = new ArrayCollection();
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): self
    {
        $this->isActive = $isActive;

        return $this;
    }

    /**
     * @return Collection<int, ProductPrice>
     */
    public function getProductPrices(): Collection
    {
        return $this->productPrices;
    }

    public function addProductPrice(ProductPrice $productPrice): self
    {
        if (!$this->productPrices->contains($productPrice)) {
            $this->productPrices->add($productPrice);
            $productPrice->setSaleZone($this);
        }

        return $this;
    }

    public function removeProductPrice(ProductPrice $productPrice): self
    {
        if ($this->productPrices->removeElement($productPrice)) {
            // set the owning side to null (unless already changed)
            if ($productPrice->getSaleZone() === $this) {
                $productPrice->setSaleZone(null);
            }
        }

        return $this;
    }
}
