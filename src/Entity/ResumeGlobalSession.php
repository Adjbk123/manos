<?php

namespace App\Entity;

use App\Repository\ResumeGlobalSessionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: ResumeGlobalSessionRepository::class)]
#[ORM\Table(name: 'session_resume_globals')]
class ResumeGlobalSession
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['session:read'])]
    private ?int $id = null;

    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?SessionService $session = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2)]
    #[Groups(['session:read'])]
    private string $valeurTheoriqueTotale = '0';

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2)]
    #[Groups(['session:read'])]
    private string $valeurConfirmeeTotale = '0';

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2)]
    #[Groups(['session:read'])]
    private string $ecartTotal = '0';

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSession(): ?SessionService
    {
        return $this->session;
    }

    public function setSession(SessionService $session): static
    {
        $this->session = $session;

        return $this;
    }

    public function getValeurTheoriqueTotale(): ?string
    {
        return $this->valeurTheoriqueTotale;
    }

    public function setValeurTheoriqueTotale(string $valeurTheoriqueTotale): static
    {
        $this->valeurTheoriqueTotale = $valeurTheoriqueTotale;

        return $this;
    }

    public function getValeurConfirmeeTotale(): ?string
    {
        return $this->valeurConfirmeeTotale;
    }

    public function setValeurConfirmeeTotale(string $valeurConfirmeeTotale): static
    {
        $this->valeurConfirmeeTotale = $valeurConfirmeeTotale;

        return $this;
    }

    public function getEcartTotal(): ?string
    {
        return $this->ecartTotal;
    }

    public function setEcartTotal(string $ecartTotal): static
    {
        $this->ecartTotal = $ecartTotal;

        return $this;
    }
}
