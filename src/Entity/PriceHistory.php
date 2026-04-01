<?php

namespace App\Entity;

use App\Repository\PriceHistoryRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: PriceHistoryRepository::class)]
#[ORM\Table(name: 'product_price_history')]
class PriceHistory
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['price_history:read', 'product:read'])]
    private ?int $id = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 2)]
    #[Groups(['price_history:read', 'product:read'])]
    private ?string $price = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Groups(['price_history:read', 'product:read'])]
    private ?\DateTimeImmutable $effectiveFrom = null;

    #[ORM\ManyToOne(inversedBy: 'priceHistories')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Product $product = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['price_history:read', 'product:read'])]
    private ?User $user = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['price_history:read', 'product:read'])]
    private ?SaleZone $saleZone = null;

    public function __construct()
    {
        $this->effectiveFrom = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getEffectiveFrom(): ?\DateTimeImmutable
    {
        return $this->effectiveFrom;
    }

    public function setEffectiveFrom(\DateTimeImmutable $effectiveFrom): self
    {
        $this->effectiveFrom = $effectiveFrom;

        return $this;
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

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

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
}
