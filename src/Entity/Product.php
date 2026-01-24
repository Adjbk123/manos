<?php

namespace App\Entity;

use App\Repository\ProductRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: ProductRepository::class)]
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

    #[ORM\OneToMany(mappedBy: 'product', targetEntity: StockBatch::class, orphanRemoval: true)]
    private Collection $stockBatches;

    public function __construct()
    {
        $this->stockBatches = new ArrayCollection();
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

    #[Groups(['stock:read'])]
    public function getSuggestedPrice(): ?float
    {
        return $this->getTargetPrice();
    }

    #[Groups(['stock:read'])]
    public function getTargetPrice(): ?float
    {
        $batches = $this->getAvailableBatches();
        return empty($batches) ? null : (float) $batches[0]->getTargetSellingPrice();
    }

    #[Groups(['stock:read'])]
    public function getMinPrice(): ?float
    {
        $batches = $this->getAvailableBatches();
        return empty($batches) ? null : (float) $batches[0]->getMinSellingPrice();
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
