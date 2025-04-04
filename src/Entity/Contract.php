<?php

namespace App\Entity;

use App\Repository\ContractRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ContractRepository::class)]
class Contract
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id_contract = null;

    #[ORM\ManyToOne(inversedBy: 'contracts')]
    private ?partnership $id_partnership = null;

    #[ORM\ManyToOne(inversedBy: 'contracts')]
    private ?partner $id_partner = null;

    #[ORM\Column(length: 255)]
    private ?string $date_debut = null;

    #[ORM\Column(length: 255)]
    private ?string $date_fin = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $terms = null;

    #[ORM\Column(length: 50)]
    private ?string $status = null;

    public function getId(): ?int
    {
        return $this->id_contract;
    }

    public function getIdPartnership(): ?partnership
    {
        return $this->id_partnership;
    }

    public function setIdPartnership(?partnership $id_partnership): static
    {
        $this->id_partnership = $id_partnership;

        return $this;
    }

    public function getIdPartner(): ?partner
    {
        return $this->id_partner;
    }

    public function setIdPartner(?partner $id_partner): static
    {
        $this->id_partner = $id_partner;

        return $this;
    }

    public function getDateDebut(): ?string
    {
        return $this->date_debut;
    }

    public function setDateDebut(string $date_debut): static
    {
        $this->date_debut = $date_debut;

        return $this;
    }

    public function getDateFin(): ?string
    {
        return $this->date_fin;
    }

    public function setDateFin(string $date_fin): static
    {
        $this->date_fin = $date_fin;

        return $this;
    }

    public function getTerms(): ?string
    {
        return $this->terms;
    }

    public function setTerms(string $terms): static
    {
        $this->terms = $terms;

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
}
