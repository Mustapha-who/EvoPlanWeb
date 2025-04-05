<?php

namespace App\Entity;

use App\Repository\EventRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Enum\Lieu;
use App\Enum\StatutEvent;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity(repositoryClass: EventRepository::class)]
class Event
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id_event = null;

    #[ORM\Column(length: 255)]
    private ?string $nom = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateDebut = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateFin = null;

    #[ORM\Column(type: "string", enumType: Lieu::class)]
    private ?Lieu $lieu = null;

    #[ORM\Column(type: "string", enumType: StatutEvent::class)]
    private ?StatutEvent $statut = null;

    #[ORM\Column]
    private ?int $capacite = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $prix = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $imageEvent = null;

    #[ORM\Column]
    private ?int $nombreVisites = 0;

    /*#[ORM\OneToMany(mappedBy: "event", targetEntity: Reservation::class)]
    private Collection $reservations;*/
    /*$this->reservations = new ArrayCollection();*/
    public function __construct()
    {
        $this->nombreVisites = 0;
        $this->statut = StatutEvent::DISPONIBLE;
    }

    public function getId_event(): ?int
    {
        return $this->id_event;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getDateDebut(): ?\DateTimeInterface
    {
        return $this->dateDebut;
    }

    public function setDateDebut(\DateTimeInterface $dateDebut): static
    {
        $this->dateDebut = $dateDebut;
        return $this;
    }

    public function getDateFin(): ?\DateTimeInterface
    {
        return $this->dateFin;
    }

    public function setDateFin(\DateTimeInterface $dateFin): static
    {
        $this->dateFin = $dateFin;
        return $this;
    }

    public function getLieu(): ?Lieu
    {
        return $this->lieu;
    }

    public function setLieu(Lieu $lieu): static
    {
        $this->lieu = $lieu;
        return $this;
    }

    public function getStatut(): ?StatutEvent
    {
        return $this->statut;
    }

    public function setStatut(StatutEvent $statut): static
    {
        $this->statut = $statut;
        return $this;
    }

    public function getCapacite(): ?int
    {
        return $this->capacite;
    }

    public function setCapacite(int $capacite): static
    {
        $this->capacite = $capacite;
        return $this;
    }

    public function getPrix(): ?string
    {
        return $this->prix;
    }

    public function setPrix(string $prix): static
    {
        $this->prix = $prix;
        return $this;
    }

    public function getImageEvent(): ?string
    {
        return $this->imageEvent;
    }

    public function setImageEvent(?string $imageEvent): static
    {
        $this->imageEvent = $imageEvent;
        return $this;
    }

    public function getNombreVisites(): ?int
    {
        return $this->nombreVisites;
    }

    public function setNombreVisites(int $nombreVisites): static
    {
        $this->nombreVisites = $nombreVisites;
        return $this;
    }
}
