<?php
namespace App\Entity\UserModule;

class UserDTO
{
    public ?int $id = null;
    public string $email = '';
    public string $name = '';
    public ?string $phoneNumber = null;
    public ?string $location = null;
    public ?EventPlannerModule $assignedModule = null;
    public ?string $specialization = null;
    public ?string $certificate = null;
    public ?bool $isApproved = null;
    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getPhoneNumber(): ?string
    {
        return $this->phoneNumber;
    }

    public function setPhoneNumber(?string $phoneNumber): void
    {
        $this->phoneNumber = $phoneNumber;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLocation(?string $location): void
    {
        $this->location = $location;
    }

    public function getAssignedModule(): ?EventPlannerModule
    {
        return $this->assignedModule;
    }

    public function setAssignedModule(?EventPlannerModule $assignedModule): void
    {
        $this->assignedModule = $assignedModule;
    }



    public function getSpecialization(): ?string
    {
        return $this->specialization;
    }

    public function setSpecialization(?string $specialization): void
    {
        $this->specialization = $specialization;
    }

    public function getCertificate(): ?string
    {
        return $this->certificate;
    }

    public function setCertificate(?string $certificate): void
    {
        $this->certificate = $certificate;
    }

    public function getIsApproved(): ?bool
    {
        return $this->isApproved;
    }

    public function setIsApproved(?bool $isApproved): void
    {
        $this->isApproved = $isApproved;
    }




}