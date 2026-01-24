<?php

namespace App\Entity;

use App\Repository\RapportSessionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: RapportSessionRepository::class)]
#[ORM\Table(name: 'rapport_sessions')]
class RapportSession
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['session:read', 'rapport:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'rapports')]
    #[ORM\JoinColumn(nullable: false)]
    private ?SessionService $session = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['session:read', 'rapport:read'])]
    private ?Account $compte = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2)]
    #[Groups(['session:read', 'rapport:read'])]
    private ?string $soldeOuverture = '0';

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2)]
    #[Groups(['session:read', 'rapport:read'])]
    private ?string $soldeTheoriqueFermeture = '0';

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2)]
    #[Groups(['session:read', 'rapport:read'])]
    private ?string $soldeConfirmeFermeture = '0';

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2)]
    #[Groups(['session:read', 'rapport:read'])]
    private ?string $totalDepots = '0';

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2)]
    #[Groups(['session:read', 'rapport:read'])]
    private ?string $totalRetraits = '0';

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2)]
    #[Groups(['session:read', 'rapport:read'])]
    private ?string $totalVentes = '0';

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2)]
    #[Groups(['session:read', 'rapport:read'])]
    private ?string $ecart = '0';

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSession(): ?SessionService
    {
        return $this->session;
    }

    public function setSession(?SessionService $session): static
    {
        $this->session = $session;

        return $this;
    }

    public function getCompte(): ?Account
    {
        return $this->compte;
    }

    public function setCompte(?Account $compte): static
    {
        $this->compte = $compte;

        return $this;
    }

    public function getSoldeOuverture(): ?string
    {
        return $this->soldeOuverture;
    }

    public function setSoldeOuverture(string $soldeOuverture): static
    {
        $this->soldeOuverture = $soldeOuverture;

        return $this;
    }

    public function getSoldeTheoriqueFermeture(): ?string
    {
        return $this->soldeTheoriqueFermeture;
    }

    public function setSoldeTheoriqueFermeture(string $soldeTheoriqueFermeture): static
    {
        $this->soldeTheoriqueFermeture = $soldeTheoriqueFermeture;

        return $this;
    }

    public function getSoldeConfirmeFermeture(): ?string
    {
        return $this->soldeConfirmeFermeture;
    }

    public function setSoldeConfirmeFermeture(string $soldeConfirmeFermeture): static
    {
        $this->soldeConfirmeFermeture = $soldeConfirmeFermeture;

        return $this;
    }

    public function getTotalDepots(): ?string
    {
        return $this->totalDepots;
    }

    public function setTotalDepots(string $totalDepots): static
    {
        $this->totalDepots = $totalDepots;

        return $this;
    }

    public function getTotalRetraits(): ?string
    {
        return $this->totalRetraits;
    }

    public function setTotalRetraits(string $totalRetraits): static
    {
        $this->totalRetraits = $totalRetraits;

        return $this;
    }

    public function getTotalVentes(): ?string
    {
        return $this->totalVentes;
    }

    public function setTotalVentes(string $totalVentes): static
    {
        $this->totalVentes = $totalVentes;

        return $this;
    }

    public function getEcart(): ?string
    {
        return $this->ecart;
    }

    public function setEcart(string $ecart): static
    {
        $this->ecart = $ecart;

        return $this;
    }
}
