<?php

namespace App\Entity;

use App\Repository\BilletageRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: BilletageRepository::class)]
#[ORM\Table(name: 'billetages')]
class Billetage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['session:read'])]
    private ?int $id = null;

    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?SessionService $session = null;

    /**
     * Structure JSON pour stocker le détail :
     * [
     *   {"valeur": 10000, "quantite": 5, "type": "billet"},
     *   {"valeur": 500, "quantite": 10, "type": "piece"},
     *   ...
     * ]
     */
    #[ORM\Column(type: Types::JSON)]
    #[Groups(['session:read'])]
    private array $details = [];

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2)]
    #[Groups(['session:read'])]
    private string $totalTheorique = '0';

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2)]
    #[Groups(['session:read'])]
    private string $totalPhysique = '0';

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2)]
    #[Groups(['session:read'])]
    private string $ecart = '0';

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

    public function getDetails(): array
    {
        return $this->details;
    }

    public function setDetails(array $details): static
    {
        $this->details = $details;

        return $this;
    }

    public function getTotalTheorique(): ?string
    {
        return $this->totalTheorique;
    }

    public function setTotalTheorique(string $totalTheorique): static
    {
        $this->totalTheorique = $totalTheorique;

        return $this;
    }

    public function getTotalPhysique(): ?string
    {
        return $this->totalPhysique;
    }

    public function setTotalPhysique(string $totalPhysique): static
    {
        $this->totalPhysique = $totalPhysique;

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
