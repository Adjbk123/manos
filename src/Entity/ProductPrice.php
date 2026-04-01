<?php

namespace App\Entity;

use App\Repository\ProductPriceRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: ProductPriceRepository::class)]
#[ORM\Table(name: 'product_prices')]
class ProductPrice
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['product:read', 'product_price:read', 'sale_zone:read', 'stock:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'productPrices')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['product_price:read', 'sale_zone:read'])]
    private ?Product $product = null;

    #[ORM\ManyToOne(inversedBy: 'productPrices')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['product:read', 'product_price:read', 'stock:read', 'stock_arrival:read'])]
    private ?SaleZone $saleZone = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 2)]
    #[Groups(['product:read', 'product_price:read', 'sale_zone:read', 'stock:read', 'stock_arrival:read'])]
    private ?string $price = null;

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

    public function getSaleZone(): ?SaleZone
    {
        return $this->saleZone;
    }

    public function setSaleZone(?SaleZone $saleZone): self
    {
        $this->saleZone = $saleZone;

        return $this;
    }

    public function getPrice(): ?string
    {
        return $this->price;
    }

    public function setPrice(string $price): self
    {
        $this->price = $price;

        return $this;
    }
}
