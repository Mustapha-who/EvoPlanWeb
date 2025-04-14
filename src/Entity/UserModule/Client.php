<?php

namespace App\Entity\UserModule;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: 'App\Repository\UserModule\ClientRepository')]
#[ORM\Table(name: 'client')]
class Client extends User
{
    #[ORM\Column(type: 'string', length: 255)]
    private string $phoneNumber= '';

    public function getPhoneNumber(): string
    {
        return $this->phoneNumber;
    }

    public function setPhoneNumber(string $phoneNumber): self
    {
        $this->phoneNumber = $phoneNumber;

        return $this;
    }
    public function getRoles(): array
    {
        return ['ROLE_CLIENT'];
    }

}