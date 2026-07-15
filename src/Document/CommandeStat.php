<?php

namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ODM\Document(collection: 'commande_stats')]
class CommandeStat
{
    #[ODM\Id]
    private ?string $id = null;

    #[ODM\Field(type: 'int')]
    private int $menuId;

    #[ODM\Field(type: 'string')]
    private string $menuTitre;

    #[ODM\Field(type: 'int')]
    private int $nombreCommandes = 0;

    #[ODM\Field(type: 'float')]
    private float $chiffreAffaires = 0.0;

    #[ODM\Field(type: 'date')]
    private \DateTime $derniereMiseAJour;

    public function __construct(int $menuId, string $menuTitre)
    {
        $this->menuId = $menuId;
        $this->menuTitre = $menuTitre;
        $this->derniereMiseAJour = new \DateTime();
    }

    public function getId(): ?string { return $this->id; }
    public function getMenuId(): int { return $this->menuId; }
    public function getMenuTitre(): string { return $this->menuTitre; }
    public function getNombreCommandes(): int { return $this->nombreCommandes; }
    public function getChiffreAffaires(): float { return $this->chiffreAffaires; }
    public function getDerniereMiseAJour(): \DateTime { return $this->derniereMiseAJour; }

    public function incrementer(float $montant): void
    {
        $this->nombreCommandes++;
        $this->chiffreAffaires += $montant;
        $this->derniereMiseAJour = new \DateTime();
    }

    public function setMenuTitre(string $titre): void { $this->menuTitre = $titre; }
}
