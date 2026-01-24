<?php

namespace App\Entity;

use App\Repository\ParametresCaisseRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: ParametresCaisseRepository::class)]
#[ORM\Table(name: 'parametres_caisse')]
class ParametresCaisse
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['session:read'])]
    private ?int $id = null;

    #[ORM\Column(type: Types::TIME_MUTABLE, nullable: true)]
    #[Groups(['session:read'])]
    private ?\DateTimeInterface $heureRappelCloture = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['session:read'])]
    private ?int $frequenceRappel = null;

    #[ORM\Column]
    #[Groups(['session:read'])]
    private bool $bloquerOperationsSiNonCloture = false;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getHeureRappelCloture(): ?\DateTimeInterface
    {
        return $this->heureRappelCloture;
    }

    public function setHeureRappelCloture(?\DateTimeInterface $heureRappelCloture): static
    {
        $this->heureRappelCloture = $heureRappelCloture;

        return $this;
    }

    public function getFrequenceRappel(): ?int
    {
        return $this->frequenceRappel;
    }

    public function setFrequenceRappel(?int $frequenceRappel): static
    {
        $this->frequenceRappel = $frequenceRappel;

        return $this;
    }

    public function isBloquerOperationsSiNonCloture(): ?bool
    {
        return $this->bloquerOperationsSiNonCloture;
    }

    public function setBloquerOperationsSiNonCloture(bool $bloquerOperationsSiNonCloture): static
    {
        $this->bloquerOperationsSiNonCloture = $bloquerOperationsSiNonCloture;

        return $this;
    }
}
