<?php

namespace App\Entity\UserModule;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: 'App\Repository\UserModule\InstructorRepository')]
#[ORM\Table(name: 'instructor')]
class Instructor extends User
{
    #[ORM\Column(type: 'string', length: 255)]
    private ?string $certification=null;

    #[ORM\Column(type: 'boolean')]
    private bool $isApproved = false;

    public function getCertification(): ?string
    {
        return $this->certification;
    }

    public function setCertification(?string $certification): self
    {
        $this->certification = $certification;

        return $this;
    }

    public function isApproved(): ?bool
    {
        return $this->isApproved;
    }

    public function setApproved(bool $isApproved): self
    {
        $this->isApproved = $isApproved;

        return $this;
    }

    public function getRoles(): array
    {
        return ['ROLE_INSTRUCTOR'];
    }
}