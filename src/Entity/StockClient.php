<?php

namespace App\Entity;

use App\Repository\StockClientRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\MaxDepth;

#[ORM\Entity(repositoryClass: StockClientRepository::class)]
#[ORM\Table(name: 'stock_clients')]
#[ORM\HasLifecycleCallbacks]
class StockClient
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['stock_client:read', 'stock_client:write', 'sale:read', 'credit:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['stock_client:read', 'stock_client:write', 'sale:read', 'credit:read'])]
    private ?string $name = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    #[Groups(['stock_client:read', 'stock_client:write', 'sale:read'])]
    private array $phones = [];

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['stock_client:read', 'stock_client:write', 'sale:read'])]
    private ?string $address = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2)]
    #[Groups(['stock_client:read', 'stock_client:write', 'sale:read'])]
    private ?string $currentDebt = '0.00';

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(['stock_client:read'])]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\OneToMany(targetEntity: Sale::class, mappedBy: 'stockClient')]
    #[Groups(['stock_client:read'])]
    #[MaxDepth(1)]
    private Collection $sales;

    #[ORM\OneToMany(targetEntity: CreditPayment::class, mappedBy: 'client')]
    #[Groups(['stock_client:read'])]
    #[MaxDepth(1)]
    private Collection $payments;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->phones = [];
        $this->sales = new ArrayCollection();
        $this->payments = new ArrayCollection();
    }

    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        $this->createdAt = new \DateTime();
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

    public function getPhones(): array
    {
        return $this->phones;
    }

    public function setPhones(?array $phones): self
    {
        $this->phones = $phones;

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

    public function getCurrentDebt(): ?string
    {
        return $this->currentDebt;
    }

    public function setCurrentDebt(string $currentDebt): self
    {
        $this->currentDebt = $currentDebt;

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
     * @return Collection<int, Sale>
     */
    public function getSales(): Collection
    {
        return $this->sales;
    }

    /**
     * @return Collection<int, CreditPayment>
     */
    public function getPayments(): Collection
    {
        return $this->payments;
    }
}
