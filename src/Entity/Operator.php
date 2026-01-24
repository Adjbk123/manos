<?php

namespace App\Entity;

use App\Repository\OperatorRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: OperatorRepository::class)]
#[ORM\Table(name: 'operators')]
class Operator
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['operator:read', 'operation_type:read', 'ussd_code:read', 'appro_request:read', 'transaction:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['operator:read', 'operator:write', 'operation_type:read', 'ussd_code:read', 'appro_request:read', 'transaction:read'])]
    private ?string $name = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['operator:read', 'operator:write', 'transaction:read'])]
    private ?string $logo = null;

    #[ORM\Column]
    #[Groups(['operator:read', 'operator:write'])]
    private ?bool $status = true;

    #[ORM\OneToMany(mappedBy: 'operator', targetEntity: Account::class, orphanRemoval: true)]
    #[Groups(['operator:read'])]
    private Collection $balances;

    public function __construct()
    {
        $this->balances = new ArrayCollection();
        $this->status = true;
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

    public function getLogo(): ?string
    {
        return $this->logo;
    }

    public function setLogo(?string $logo): static
    {
        $this->logo = $logo;

        return $this;
    }

    public function isStatus(): ?bool
    {
        return $this->status;
    }

    public function setStatus(bool $status): static
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @return Collection<int, Account>
     */
    public function getBalances(): Collection
    {
        return $this->balances;
    }

    public function addBalance(Account $balance): static
    {
        if (!$this->balances->contains($balance)) {
            $this->balances->add($balance);
            $balance->setOperator($this);
        }

        return $this;
    }

    public function removeBalance(Account $balance): static
    {
        if ($this->balances->removeElement($balance)) {
            // set the owning side to null (unless already changed)
            if ($balance->getOperator() === $this) {
                $balance->setOperator(null);
            }
        }

        return $this;
    }
}
