<?php

namespace App\Entity;

use App\Repository\MenuRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MenuRepository::class)]
class Menu
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $titre = null;

    #[ORM\Column]
    private ?int $nombre_personne_minimum = null;

    #[ORM\Column]
    private ?float $prix_par_personne = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $description = null;

    #[ORM\Column]
    private ?int $quantite_restante = null;

    /**
     * @var Collection<int, Commande>
     */
    #[ORM\OneToMany(targetEntity: Commande::class, mappedBy: 'menu')]
    private Collection $commandes;

    #[ORM\ManyToOne(inversedBy: 'menus')]
    private ?Regime $regime = null;

    #[ORM\ManyToOne(inversedBy: 'menus')]
    private ?Theme $theme = null;

    /**
     * @var Collection<int, Plat>
     */
    #[ORM\ManyToMany(targetEntity: Plat::class, inversedBy: 'menus')]
    private Collection $plats;

    public function __construct()
    {
        $this->commandes = new ArrayCollection();
        $this->plats = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): static
    {
        $this->titre = $titre;

        return $this;
    }

    public function getNombrePersonneMinimum(): ?int
    {
        return $this->nombre_personne_minimum;
    }

    public function setNombrePersonneMinimum(int $nombre_personne_minimum): static
    {
        $this->nombre_personne_minimum = $nombre_personne_minimum;

        return $this;
    }

    public function getPrixParPersonne(): ?float
    {
        return $this->prix_par_personne;
    }

    public function setPrixParPersonne(float $prix_par_personne): static
    {
        $this->prix_par_personne = $prix_par_personne;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getQuantiteRestante(): ?int
    {
        return $this->quantite_restante;
    }

    public function setQuantiteRestante(int $quantite_restante): static
    {
        $this->quantite_restante = $quantite_restante;

        return $this;
    }

    /**
     * @return Collection<int, Commande>
     */
    public function getCommandes(): Collection
    {
        return $this->commandes;
    }

    public function addCommande(Commande $commande): static
    {
        if (!$this->commandes->contains($commande)) {
            $this->commandes->add($commande);
            $commande->setMenu($this);
        }

        return $this;
    }

    public function removeCommande(Commande $commande): static
    {
        if ($this->commandes->removeElement($commande)) {
            // set the owning side to null (unless already changed)
            if ($commande->getMenu() === $this) {
                $commande->setMenu(null);
            }
        }

        return $this;
    }

    public function getRegime(): ?Regime
    {
        return $this->regime;
    }

    public function setRegime(?Regime $regime): static
    {
        $this->regime = $regime;

        return $this;
    }

    public function getTheme(): ?Theme
    {
        return $this->theme;
    }

    public function setTheme(?Theme $theme): static
    {
        $this->theme = $theme;

        return $this;
    }

    /**
     * @return Collection<int, Plat>
     */
    public function getPlats(): Collection
    {
        return $this->plats;
    }

    public function addPlat(Plat $plat): static
    {
        if (!$this->plats->contains($plat)) {
            $this->plats->add($plat);
        }

        return $this;
    }

    public function removePlat(Plat $plat): static
    {
        $this->plats->removeElement($plat);

        return $this;
    }
}
