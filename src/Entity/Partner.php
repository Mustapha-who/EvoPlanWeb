<?php

namespace App\Entity;

use App\Repository\PartnerRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PartnerRepository::class)]
class Partner
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id_partner = null;

    #[ORM\Column(length: 200)]
    #[Assert\NotBlank(message: 'Type is required')]
    private ?string $type_partner = null;

    #[ORM\Column(length: 200, unique: true)]
    #[Assert\NotBlank(message: 'Email is required')]
    #[Assert\Email(message: 'The email {{ value }} is not a valid email.')]
    private ?string $email = null;

    #[ORM\Column(length: 13, unique: true)]
    #[Assert\NotBlank(message: 'Phone number is required')]
    #[Assert\Length(max: 8, maxMessage: 'Phone number cannot be longer than {{ limit }} digits')]
    #[Assert\Regex(pattern: '/^\d+$/', message: 'Phone number must contain only digits')]
    private ?string $phone_Number = null;

    #[ORM\Column(length: 255,  unique: true)]
    private ?string $logo = null;

    /**
     * @var Collection<int, Partnership>
     */
    #[ORM\OneToMany(targetEntity: Partnership::class, mappedBy: 'id_partner', cascade: ['remove'])]
    private Collection $partnerships;

    /**
     * @var Collection<int, Contract>
     */
    #[ORM\OneToMany(targetEntity: Contract::class, mappedBy: 'id_partner', cascade: ['remove'])]
    private Collection $contracts;

    public function __construct()
    {
        $this->partnerships = new ArrayCollection();
        $this->contracts = new ArrayCollection();
    }

    public function getId_partner(): ?int
    {
        return $this->id_partner;
    }

    public function getTypePartner(): ?string
    {
        return $this->type_partner;
    }

    public function setTypePartner(string $type_partner): static
    {
        $this->type_partner = $type_partner;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getPhoneNumber(): ?string
    {
        return $this->phone_Number;
    }

    public function setPhoneNumber(string $phone_Number): static
    {
        $this->phone_Number = $phone_Number;

        return $this;
    }

    public function getLogo(): ?string
    {
        return $this->logo;
    }

    public function setLogo(?string $logo): static
    {
        $this->logo = $logo;

        return $this;
    }

    /**
     * @return Collection<int, Partnership>
     */
    public function getPartnerships(): Collection
    {
        return $this->partnerships;
    }

    public function addPartnership(Partnership $partnership): static
    {
        if (!$this->partnerships->contains($partnership)) {
            $this->partnerships->add($partnership);
            $partnership->setIdPartner($this);
        }

        return $this;
    }

    public function removePartnership(Partnership $partnership): static
    {
        if ($this->partnerships->removeElement($partnership)) {
            // set the owning side to null (unless already changed)
            if ($partnership->getIdPartner() === $this) {
                $partnership->setIdPartner(null);
            }
        }

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
            $contract->setIdPartner($this);
        }

        return $this;
    }

    public function removeContract(Contract $contract): static
    {
        if ($this->contracts->removeElement($contract)) {
            // set the owning side to null (unless already changed)
            if ($contract->getIdPartner() === $this) {
                $contract->setIdPartner(null);
            }
        }

        return $this;
    }
}
