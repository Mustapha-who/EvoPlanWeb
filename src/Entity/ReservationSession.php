<?php

namespace App\Entity;

use App\Repository\ReservationSessionRepository;
use App\Entity\UserModule\Client;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ReservationSessionRepository::class)]
class ReservationSession
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Session::class, inversedBy: 'reservationSessions')]
    #[ORM\JoinColumn(name: 'id_session', referencedColumnName: 'id_session', nullable: false)]
    private ?Session $id_session = null;

    #[ORM\ManyToOne(targetEntity: Client::class)]
    #[ORM\JoinColumn(name: 'id_participants', referencedColumnName: 'id', nullable: false)]
    private ?Client $participant = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIdSession(): ?Session
    {
        return $this->id_session;
    }

    public function setIdSession(?Session $id_session): static
    {
        $this->id_session = $id_session;

        return $this;
    }

    public function getParticipant(): ?Client
    {
        return $this->participant;
    }

    public function setParticipant(?Client $participant): static
    {
        $this->participant = $participant;
        return $this;
    }
}
