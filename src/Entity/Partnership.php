<?php

namespace App\Entity;

use App\Repository\PartnershipRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: PartnershipRepository::class)]
#[UniqueEntity(
    fields: ['id_partner', 'id_event'],
    message: 'This partner is already associated with this event.'
)]
class Partnership
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id_partnership = null;

    #[ORM\ManyToOne(targetEntity: Partner::class, inversedBy: 'partnerships')]
    #[ORM\JoinColumn(name: 'id_partner', referencedColumnName: 'id_partner')]
    #[Assert\NotNull(message: 'Please select a partner.')]
    private ?Partner $id_partner = null;

    #[ORM\ManyToOne(targetEntity: Event::class, inversedBy: 'partnerships')]
    #[ORM\JoinColumn(name: 'id_event', referencedColumnName: 'id_event')]
    #[Assert\NotNull(message: 'Please select an event.')]
    private ?Event $id_event = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Assert\NotNull(message: 'Start date is required.')]
    #[Assert\Type("\DateTimeInterface", message: 'Please enter a valid date.')]
    #[Assert\GreaterThanOrEqual(
        value: "today",
        message: "Start date must be today or in the future."
    )]
    private ?\DateTimeInterface $date_debut = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    #[Assert\NotNull(message: 'End date is required.')]
    #[Assert\Type("\DateTimeInterface", message: 'Please enter a valid date.')]
    #[Assert\Expression(
        "this.getDateFin() > this.getDateDebut()",
        message: "End date must be after start date."
    )]
    private ?\DateTimeInterface $date_fin = null;

    #[ORM\Column(type: Types::TEXT, nullable: false)]
    #[Assert\NotBlank(message: 'Terms and conditions are required.')]
    private ?string $terms = null;

    /**
     * @var Collection<int, Contract>
     */
    #[ORM\OneToMany(targetEntity: Contract::class, mappedBy: 'id_partnership', cascade: ['remove'])]
    private Collection $contracts;

    public function __construct()
    {
        $this->contracts = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id_partnership;
    }

    public function getIdPartnership(): ?int
    {
        return $this->id_partnership;
    }

    public function getIdPartner(): ?Partner
    {
        return $this->id_partner;
    }

    public function setIdPartner(?Partner $id_partner): static
    {
        $this->id_partner = $id_partner;
        return $this;
    }

    public function getIdEvent(): ?Event
    {
        return $this->id_event;
    }

    public function setIdEvent(?Event $id_event): static
    {
        $this->id_event = $id_event;

        return $this;
    }

    public function getDateDebut(): ?\DateTimeInterface
    {
        return $this->date_debut;
    }

    public function setDateDebut(\DateTimeInterface $date_debut): static
    {
        $this->date_debut = $date_debut;

        return $this;
    }

    public function getDateFin(): ?\DateTimeInterface
    {
        return $this->date_fin;
    }

    public function setDateFin(?\DateTimeInterface $date_fin): static
    {
        $this->date_fin = $date_fin;

        return $this;
    }

    public function getTerms(): ?string
    {
        return $this->terms;
    }

    public function setTerms(?string $terms): static
    {
        $this->terms = $terms;

        return $this;
    }

    /**
     * @return Collection<int, Contract>
     */
    public function getContracts(): Collection
    {
        return $this->contracts;
    }

    public function addContract(Contract $contract): static
    {
        if (!$this->contracts->contains($contract)) {
            $this->contracts->add($contract);
            $contract->setIdPartnership($this);
        }

        return $this;
    }

    public function removeContract(Contract $contract): static
    {
        if ($this->contracts->removeElement($contract)) {
            // set the owning side to null (unless already changed)
            if ($contract->getIdPartnership() === $this) {
                $contract->setIdPartnership(null);
            }
        }

        return $this;
    }
}
