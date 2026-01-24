<?php

namespace App\Entity;

use App\Repository\SessionServiceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: SessionServiceRepository::class)]
#[ORM\Table(name: 'session_services')]
class SessionService
{
    public const STATUS_OPEN = 'OUVERT';
    public const STATUS_CLOSED = 'FERMÉ';

    public const TYPE_SIMPLE = 'CLOTURE_SIMPLE';
    public const TYPE_HANDOVER = 'PASSATION_DIRECTE';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['session:read', 'rapport:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['session:read'])]
    private ?User $agent = null;

    #[ORM\Column]
    #[Groups(['session:read'])]
    private ?\DateTimeImmutable $startedAt = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['session:read'])]
    private ?\DateTimeImmutable $endedAt = null;

    #[ORM\Column(length: 20)]
    #[Groups(['session:read'])]
    private ?string $status = self::STATUS_OPEN;

    #[ORM\Column(length: 20, nullable: true)]
    #[Groups(['session:read'])]
    private ?string $typeFermeture = null;

    #[ORM\OneToOne(targetEntity: self::class, cascade: ['persist', 'remove'])]
    #[Groups(['session:read'])]
    private ?self $sessionSuivante = null;

    #[ORM\OneToMany(mappedBy: 'session', targetEntity: RapportSession::class, cascade: ['persist', 'remove'])]
    private Collection $rapports;

    public function __construct()
    {
        $this->startedAt = new \DateTimeImmutable();
        $this->rapports = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAgent(): ?User
    {
        return $this->agent;
    }

    public function setAgent(?User $agent): static
    {
        $this->agent = $agent;

        return $this;
    }

    public function getStartedAt(): ?\DateTimeImmutable
    {
        return $this->startedAt;
    }

    public function setStartedAt(\DateTimeImmutable $startedAt): static
    {
        $this->startedAt = $startedAt;

        return $this;
    }

    public function getEndedAt(): ?\DateTimeImmutable
    {
        return $this->endedAt;
    }

    public function setEndedAt(?\DateTimeImmutable $endedAt): static
    {
        $this->endedAt = $endedAt;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getTypeFermeture(): ?string
    {
        return $this->typeFermeture;
    }

    public function setTypeFermeture(?string $typeFermeture): static
    {
        $this->typeFermeture = $typeFermeture;

        return $this;
    }

    public function getSessionSuivante(): ?self
    {
        return $this->sessionSuivante;
    }

    public function setSessionSuivante(?self $sessionSuivante): static
    {
        $this->sessionSuivante = $sessionSuivante;

        return $this;
    }

    /**
     * @return Collection<int, RapportSession>
     */
    public function getRapports(): Collection
    {
        return $this->rapports;
    }

    public function addRapport(RapportSession $rapport): static
    {
        if (!$this->rapports->contains($rapport)) {
            $this->rapports->add($rapport);
            $rapport->setSession($this);
        }

        return $this;
    }

    public function removeRapport(RapportSession $rapport): static
    {
        if ($this->rapports->removeElement($rapport)) {
            // set the owning side to null (unless already changed)
            if ($rapport->getSession() === $this) {
                $rapport->setSession(null);
            }
        }

        return $this;
    }
}
