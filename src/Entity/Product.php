<?php

namespace App\Entity;

use App\Repository\ProductRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: ProductRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Product
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['product:read', 'product:write', 'stock:read', 'sale:read', 'stock_arrival:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['product:read', 'product:write', 'stock:read', 'sale:read', 'stock_arrival:read'])]
    private ?string $name = null;

    #[ORM\ManyToOne(targetEntity: ProductCategory::class, inversedBy: 'products')]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['product:read', 'product:write', 'stock:read', 'stock_arrival:read'])]
    private ?ProductCategory $category = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['product:read', 'product:write', 'stock:read'])]
    private ?string $description = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['product:read', 'product:write', 'stock:read', 'sale:read'])]
    private ?string $image = null;

    #[ORM\Column]
    #[Groups(['product:read', 'stock:read'])]
    private ?int $stockQuantity = 0;

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 2, nullable: true)]
    #[Groups(['product:read', 'product:write', 'stock:read', 'sale:read'])]
    private ?string $sellingPrice = null;

    #[ORM\Column]
    #[Groups(['product:read', 'stock:read'])]
    private int $alertThreshold = 5;

    #[ORM\Column]
    #[Groups(['product:read', 'stock:read'])]
    private bool $isActive = true;

    #[ORM\OneToMany(mappedBy: 'product', targetEntity: StockBatch::class, orphanRemoval: true)]
    #[Groups(['product:read', 'stock:read'])]
    private Collection $stockBatches;

    /**
     * @var Collection<int, PriceHistory>
     */
    #[ORM\OneToMany(mappedBy: 'product', targetEntity: PriceHistory::class, cascade: ['persist', 'remove'])]
    #[Groups(['product:read'])]
    private Collection $priceHistories;

    /**
     * @var Collection<int, ProductPrice>
     */
    #[ORM\OneToMany(mappedBy: 'product', targetEntity: ProductPrice::class, orphanRemoval: true, cascade: ['persist', 'remove'])]
    #[Groups(['product:read', 'stock:read', 'stock_arrival:read'])]
    private Collection $productPrices;

    // To track price changes
    private ?string $originalPrice = null;

    public function __construct()
    {
        $this->stockBatches = new ArrayCollection();
        $this->priceHistories = new ArrayCollection();
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

    public function getCategory(): ?ProductCategory
    {
        return $this->category;
    }

    public function setCategory(?ProductCategory $category): self
    {
        $this->category = $category;

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

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): self
    {
        $this->image = $image;

        return $this;
    }

    public function getStockQuantity(): ?int
    {
        return $this->stockQuantity;
    }

    public function setStockQuantity(int $stockQuantity): self
    {
        $this->stockQuantity = $stockQuantity;

        return $this;
    }

    public function getSellingPrice(): ?string
    {
        return $this->sellingPrice;
    }

    public function setSellingPrice(?string $sellingPrice): self
    {
        $this->sellingPrice = $sellingPrice;

        return $this;
    }

    public function getAlertThreshold(): int
    {
        return $this->alertThreshold;
    }

    public function setAlertThreshold(int $alertThreshold): self
    {
        $this->alertThreshold = $alertThreshold;

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
            $stockBatch->setProduct($this);
        }

        return $this;
    }

    public function removeStockBatch(StockBatch $stockBatch): self
    {
        if ($this->stockBatches->removeElement($stockBatch)) {
            // set the owning side to null (unless already changed)
            if ($stockBatch->getProduct() === $this) {
                $stockBatch->setProduct(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, PriceHistory>
     */
    public function getPriceHistories(): Collection
    {
        return $this->priceHistories;
    }

    #[ORM\PostLoad]
    public function storeOriginalPrice(): void
    {
        $this->originalPrice = $this->sellingPrice;
    }

    public function getOriginalPrice(): ?string
    {
        return $this->originalPrice;
    }

    #[Groups(['stock:read'])]
    public function getSuggestedPrice(): ?float
    {
        // If sellingPrice is explicitly set on product, use it
        if ($this->sellingPrice !== null) {
            return (float) $this->sellingPrice;
        }
        // Otherwise fall back to FIFO batch price
        return $this->getTargetPrice();
    }

    #[Groups(['stock:read'])]
    public function getTargetPrice(): ?float
    {
        $batches = $this->getAvailableBatches();
        return empty($batches) ? null : (float) $batches[0]->getTargetSellingPrice();
    }


    #[Groups(['stock:read'])]
    public function getLastPurchasePrice(): ?float
    {
        if ($this->stockBatches->isEmpty()) {
            return null;
        }

        $batches = $this->stockBatches->toArray();
        usort($batches, function ($a, $b) {
            return $b->getPurchaseDate() <=> $a->getPurchaseDate();
        });

        return (float) $batches[0]->getPurchasePrice();
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
            $productPrice->setProduct($this);
        }

        return $this;
    }

    public function removeProductPrice(ProductPrice $productPrice): self
    {
        if ($this->productPrices->removeElement($productPrice)) {
            // set the owning side to null (unless already changed)
            if ($productPrice->getProduct() === $this) {
                $productPrice->setProduct(null);
            }
        }

        return $this;
    }

    private function getAvailableBatches(): array
    {
        $batches = $this->stockBatches->filter(function (StockBatch $batch) {
            return $batch->getQuantityRemaining() > 0;
        })->toArray();

        // Sort by purchaseDate ASC (FIFO)
        usort($batches, function (StockBatch $a, StockBatch $b) {
            return $a->getPurchaseDate() <=> $b->getPurchaseDate();
        });

        return $batches;
    }
}
