<?php

namespace App\Entity;

use App\Entity\UserModule\Client;
use Doctrine\ORM\Mapping as ORM;
use App\Enum\StatutReservation;
use App\Entity\Event;

#[ORM\Entity]
#[ORM\Table(name: "reservation")]
class Reservation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private int $id_reservation;

    #[ORM\ManyToOne(targetEntity: Client::class)]
    #[ORM\JoinColumn(name: "id_client", referencedColumnName: "id", nullable: true)]
    private ?Client $id_client;

    #[ORM\ManyToOne(targetEntity: Event::class)]
    #[ORM\JoinColumn(name: "id_event", referencedColumnName: "id_event", nullable: true)]
    private ?Event $id_event;

    #[ORM\Column(type: "string", enumType: StatutReservation::class, options: ["default" => "CONFIRMEEE"])]
    private StatutReservation $statut;

    // --- Constructeur ---
    public function __construct()
    {
        $this->statut = StatutReservation::CONFIRMEE;
    }

    // --- Getters et Setters ---
    public function getIdReservation(): int
    {
        return $this->id_reservation;
    }

    public function getClient(): ?Client
    {
        return $this->id_client;
    }

    public function setClient(?Client $id_client): self
    {
        $this->id_client = $id_client;
        return $this;
    }

    public function getEvent(): ?Event
    {
        return $this->id_event;
    }

    public function setEvent(?Event $id_event): self
    {
        $this->id_event = $id_event;
        return $this;
    }

    public function getstatut(): StatutReservation
    {
        return $this->statut;
    }

    public function setstatut(StatutReservation $statut): self
    {
        $this->statut = $statut;
        return $this;
    }
}
