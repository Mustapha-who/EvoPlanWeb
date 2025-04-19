<?php

namespace App\Entity;

use App\Repository\EquipementRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EquipementRepository::class)]
class Equipement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le type d'équipement est obligatoire")]
    #[Assert\Length(
        min: 2,
        max: 100,
        minMessage: "Le type doit contenir au moins {{ limit }} caractères",
        maxMessage: "Le type ne peut pas dépasser {{ limit }} caractères"
    )]
    private ?string $equipementType = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: "La quantité est obligatoire")]
    #[Assert\PositiveOrZero(message: "La quantité doit être un nombre positif")]
    #[Assert\LessThan(
        value: 1000,
        message: "La quantité ne peut pas dépasser {{ value }}"
    )]
    private ?int $quantity = null;

    #[ORM\ManyToOne(inversedBy: 'equipements')]
    #[Assert\NotNull(message: "La ressource est obligatoire")]
    private ?Ressource $ressource = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEquipementType(): ?string
    {
        return $this->equipementType;
    }

    public function setEquipementType(string $equipementType): static
    {
        $this->equipementType = $equipementType;

        return $this;
    }

    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): static
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function getRessource(): ?Ressource
    {
        return $this->ressource;
    }

    public function setRessource(?Ressource $ressource): static
    {
        $this->ressource = $ressource;

        return $this;
    }
}
