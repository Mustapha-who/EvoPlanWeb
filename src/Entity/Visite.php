<?php

namespace App\Entity;

use App\Repository\VisiteRepository;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\UserModule\Client;
use App\Entity\Event;

#[ORM\Entity(repositoryClass: VisiteRepository::class)]
class Visite
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Event::class)]
    #[ORM\JoinColumn(name: "event_id", referencedColumnName: "id_event", nullable: true, onDelete: "CASCADE")]
    private ?Event $event = null;

    #[ORM\ManyToOne(targetEntity: Client::class)]
    #[ORM\JoinColumn(name: "client_id", referencedColumnName: "id", nullable: true, onDelete: "SET NULL")]
    private ?Client $client = null;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $dateVisite;

    public function __construct()
    {
        $this->dateVisite = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEvent(): ?Event
    {
        return $this->event;
    }

    public function setEvent(?Event $event): self
    {
        $this->event = $event;
        return $this;
    }

    public function getClient(): ?Client
    {
        return $this->client;
    }

    public function setClient(?Client $client): self
    {
        $this->client = $client;
        return $this;
    }

    public function getDateVisite(): \DateTimeInterface
    {
        return $this->dateVisite;
    }

    public function setDateVisite(\DateTimeInterface $dateVisite): self
    {
        $this->dateVisite = $dateVisite;
        return $this;
    }
}