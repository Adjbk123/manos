<?php

namespace App\Entity;

use App\Repository\ResumeDetailSessionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: ResumeDetailSessionRepository::class)]
#[ORM\Table(name: 'session_resume_details')]
class ResumeDetailSession
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['session:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?SessionService $session = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['session:read'])]
    private ?Operator $operateur = null;

    #[ORM\Column(length: 20)]
    #[Groups(['session:read'])]
    private ?string $typeBesognee = null; // DEPOT, RETRAIT, VENTE

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2)]
    #[Groups(['session:read'])]
    private string $volume = '0';

    #[ORM\Column]
    #[Groups(['session:read'])]
    private int $nombre = 0;

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

    public function getOperateur(): ?Operator
    {
        return $this->operateur;
    }

    public function setOperateur(?Operator $operateur): static
    {
        $this->operateur = $operateur;

        return $this;
    }

    public function getTypeBesognee(): ?string
    {
        return $this->typeBesognee;
    }

    public function setTypeBesognee(string $typeBesognee): static
    {
        $this->typeBesognee = $typeBesognee;

        return $this;
    }

    public function getVolume(): ?string
    {
        return $this->volume;
    }

    public function setVolume(string $volume): static
    {
        $this->volume = $volume;

        return $this;
    }

    public function getNombre(): ?int
    {
        return $this->nombre;
    }

    public function setNombre(int $nombre): static
    {
        $this->nombre = $nombre;

        return $this;
    }
}
