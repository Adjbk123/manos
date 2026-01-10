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
    public const CATEGORY_MOBILE_MONEY = 'Opérations Mobile Money';
    public const CATEGORY_CREDIT_DATA = 'Crédit & Forfaits';

    public const VARIANT_DAY = 'Jour';
    public const VARIANT_WEEK = 'Semaine';
    public const VARIANT_MONTH = 'Mois';
    public const VARIANT_FREE_AMOUNT = 'Montant Libre';

    public const CATEGORIES = [
        self::CATEGORY_MOBILE_MONEY,
        self::CATEGORY_CREDIT_DATA,
    ];

    public const VARIANTS = [
        self::VARIANT_DAY,
        self::VARIANT_WEEK,
        self::VARIANT_MONTH,
        self::VARIANT_FREE_AMOUNT,
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['operation_type:read', 'ussd_code:read', 'operator:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'operationTypes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Operator $operator = null;

    #[ORM\Column(length: 255)]
    #[Groups(['operation_type:read', 'operation_type:write', 'ussd_code:read', 'operator:read'])]
    private ?string $name = null;

    #[ORM\Column(length: 50, nullable: true)]
    #[Groups(['operation_type:read', 'operation_type:write'])]
    private ?string $code = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['operation_type:read', 'operation_type:write'])]
    private ?string $description = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Groups(['operation_type:read', 'operation_type:write', 'operator:read'])]
    private ?string $category = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Groups(['operation_type:read', 'operation_type:write', 'operator:read'])]
    private ?string $variant = null;

    #[ORM\Column(length: 50)]
    #[Groups(['operation_type:read', 'operation_type:write', 'operator:read'])]
    private ?string $method = 'USSD';

    #[ORM\OneToOne(mappedBy: 'operationType', targetEntity: UssdCode::class, cascade: ['persist', 'remove'])]
    #[Groups(['operation_type:read', 'operator:read'])]
    private ?UssdCode $ussdCode = null;

    public function __construct()
    {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOperator(): ?Operator
    {
        return $this->operator;
    }

    public function setOperator(?Operator $operator): static
    {
        $this->operator = $operator;

        return $this;
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

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(?string $code): static
    {
        $this->code = $code;

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

    public function getVariant(): ?string
    {
        return $this->variant;
    }

    public function setVariant(?string $variant): static
    {
        $this->variant = $variant;

        return $this;
    }

    public function getMethod(): ?string
    {
        return $this->method;
    }

    public function setMethod(string $method): static
    {
        $this->method = $method;

        return $this;
    }

    public function getUssdCode(): ?UssdCode
    {
        return $this->ussdCode;
    }

    public function setUssdCode(?UssdCode $ussdCode): static
    {
        // unset the owning side of the relation if necessary
        if ($ussdCode === null && $this->ussdCode !== null) {
            $this->ussdCode->setOperationType(null);
        }

        // set the owning side of the relation if necessary
        if ($ussdCode !== null && $ussdCode->getOperationType() !== $this) {
            $ussdCode->setOperationType($this);
        }

        $this->ussdCode = ussdCode;

        return $this;
    }
}
