<?php

namespace App\Entity;

use App\Repository\SessionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SessionRepository::class)]
class Session
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: "id_session", type: "integer")]
    private ?int $id_session = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $date = null;

    #[ORM\Column(type: Types::TIME_MUTABLE)]
    private ?\DateTimeInterface $dateheuredeb = null;

    #[ORM\Column(type: Types::TIME_MUTABLE)]
    private ?\DateTimeInterface $dateheurefin = null;

    #[ORM\Column]
    private ?int $participant_count = null;

    #[ORM\Column]
    private ?int $capacity = null;

    #[ORM\ManyToOne(targetEntity: Workshop::class, inversedBy: 'sessions')]
    #[ORM\JoinColumn(name: "id_workshop", referencedColumnName: "id_workshop")]
    private ?Workshop $id_workshop = null;

    public function getid_session(): ?int
    {
        return $this->id_session;
    }

    public function setIdSession(int $id_session): static
    {
        $this->id_session = $id_session;

        return $this;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): static
    {
        $this->date = $date;

        return $this;
    }

    public function getDateheuredeb(): ?\DateTimeInterface
    {
        return $this->dateheuredeb;
    }

    public function setDateheuredeb(\DateTimeInterface $dateheuredeb): static
    {
        $this->dateheuredeb = $dateheuredeb;

        return $this;
    }

    public function getDateheurefin(): ?\DateTimeInterface
    {
        return $this->dateheurefin;
    }

    public function setDateheurefin(\DateTimeInterface $dateheurefin): static
    {
        $this->dateheurefin = $dateheurefin;

        return $this;
    }

    public function getParticipantCount(): ?int
    {
        return $this->participant_count;
    }

    public function setParticipantCount(int $participant_count): static
    {
        $this->participant_count = $participant_count;

        return $this;
    }

    public function getCapacity(): ?int
    {
        return $this->capacity;
    }

    public function setCapacity(int $capacity): static
    {
        $this->capacity = $capacity;

        return $this;
    }

    public function getIdWorkshop(): ?workshop
    {
        return $this->id_workshop;
    }

    public function setIdWorkshop(?workshop $id_workshop): static
    {
        $this->id_workshop = $id_workshop;

        return $this;
    }


}
