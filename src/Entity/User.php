<?php

namespace App\Entity\UserModule;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\DiscriminatorMap;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: 'App\Repository\UserModule\UserRepository')]
#[ORM\Table(name: 'user')]
#[ORM\InheritanceType('JOINED')]
#[ORM\DiscriminatorColumn(name: 'dtype', type: 'string', length: 255)]
#[DiscriminatorMap([
    "admin" => Administrator::class,
    "eventplanner" => EventPlanner::class,
    "instructor" => Instructor::class,
    "client" => Client::class,
])]
class User
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id', type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(name: 'email', type: 'string', length: 180, unique: true)]
    private string $email = '';

    #[ORM\Column(name: 'password', type: 'string')]
    private string $password = '';

    #[ORM\Column(name: 'name', type: 'string', length: 255)]
    private string $name = '';

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;
        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function setId($id): void
    {
        $this->id = $id;
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }


}