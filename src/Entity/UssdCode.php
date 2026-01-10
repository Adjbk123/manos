<?php

namespace App\Entity;

use App\Repository\UssdCodeRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: UssdCodeRepository::class)]
#[ORM\Table(name: 'ussd_codes')]
class UssdCode
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['ussd_code:read', 'operator:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Operator $operator = null;

    #[ORM\OneToOne(inversedBy: 'ussdCode', targetEntity: OperationType::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?OperationType $operationType = null;

    #[ORM\Column(length: 255)]
    #[Groups(['ussd_code:read', 'ussd_code:write', 'operator:read'])]
    private ?string $template = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    #[Groups(['ussd_code:read', 'ussd_code:write', 'operator:read'])]
    private ?array $parameters = null;

    #[ORM\Column(length: 50)]
    #[Groups(['ussd_code:read', 'ussd_code:write', 'operator:read'])]
    private ?string $method = 'USSD';

    #[ORM\Column]
    #[Groups(['ussd_code:read', 'ussd_code:write'])]
    private ?bool $isEditable = true;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['ussd_code:read', 'ussd_code:write', 'operator:read'])]
    private ?string $notes = null;

    public function __construct()
    {
        $this->method = 'USSD';
        $this->isEditable = true;
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

    public function getOperationType(): ?OperationType
    {
        return $this->operationType;
    }

    public function setOperationType(?OperationType $operationType): static
    {
        $this->operationType = $operationType;

        return $this;
    }

    public function getTemplate(): ?string
    {
        return $this->template;
    }

    public function setTemplate(string $template): static
    {
        $this->template = $template;

        return $this;
    }

    public function getParameters(): ?array
    {
        return $this->parameters;
    }

    public function setParameters(?array $parameters): static
    {
        $this->parameters = $parameters;

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

    public function isIsEditable(): ?bool
    {
        return $this->isEditable;
    }

    public function setIsEditable(bool $isEditable): static
    {
        $this->isEditable = $isEditable;

        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): static
    {
        $this->notes = $notes;

        return $this;
    }
}
