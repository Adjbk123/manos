<?php

namespace App\Entity;

use App\Repository\OperationTypeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: OperationTypeRepository::class)]
#[ORM\Table(name: 'operation_types')]
class OperationType
{
    public const CATEGORY_MOBILE_MONEY = 'MOBILE_MONEY';
    public const CATEGORY_CREDIT_DATA = 'CREDIT_FORFAIT';

    public const CATEGORIES = [
        self::CATEGORY_MOBILE_MONEY => 'Opérations Mobile Money',
        self::CATEGORY_CREDIT_DATA => 'Crédit & Forfaits',
    ];

    public const CODE_DEPOSIT = 'DEPOSIT'; // Dépôt : Agent envoie UV, reçoit Cash
    public const CODE_WITHDRAWAL = 'WITHDRAWAL'; // Retrait : Agent donne Cash, reçoit UV
    public const CODE_CREDIT = 'CREDIT'; // Vente de crédit : Agent envoie UV (virtual/credit), reçoit Cash

    public const CODES = [
        self::CODE_DEPOSIT => 'Dépôt',
        self::CODE_WITHDRAWAL => 'Retrait',
        self::CODE_CREDIT => 'Vente de Crédit/Data',
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['operation_type:read', 'operator:read', 'transaction:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['operation_type:read', 'operation_type:write', 'operator:read', 'transaction:read'])]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['operation_type:read', 'operation_type:write'])]
    private ?string $description = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Groups(['operation_type:read', 'operation_type:write', 'operator:read', 'transaction:read'])]
    private ?string $category = null;

    #[ORM\Column(length: 50, nullable: true)]
    #[Groups(['operation_type:read', 'operation_type:write', 'operator:read', 'transaction:read'])]
    private ?string $code = null;

    public function __construct()
    {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(?string $category): static
    {
        $this->category = $category;

        return $this;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(?string $code): static
    {
        $this->code = $code;

        return $this;
    }

    /**
     * @Groups({"operation_type:read", "operator:read", "transaction:read"})
     */
    public function getCategoryLabel(): string
    {
        return self::CATEGORIES[$this->category] ?? $this->category ?? '';
    }
}
