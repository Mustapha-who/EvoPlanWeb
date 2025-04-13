<?php

namespace App\Entity\UserModule;

use Doctrine\ORM\Mapping as ORM;


#[ORM\Entity(repositoryClass: 'App\Repository\UserModule\EventPlannerRepository')]
#[ORM\Table(name: 'eventplanner')]
class EventPlanner extends User
{
    #[ORM\Column(type: 'string', length: 255)]
    private ?string $specialization ='';

    #[ORM\Column(enumType: EventPlannerModule::class)]
    private ?EventPlannerModule $assignedModule = null;

    public function getSpecialization(): ?string
    {
        return $this->specialization;
    }

    public function setSpecialization(?string $specialization): self
    {
        $this->specialization = $specialization;

        return $this;
    }

    public function getAssignedModule(): ?EventPlannerModule
    {
        return $this->assignedModule;
    }

    public function setAssignedModule(EventPlannerModule $assignedModule): void
    {
        $this->assignedModule = $assignedModule;
    }

    public function getRoles(): array
    {
        return ['ROLE_EVENTPLANNER'];
    }

    public function __toString(): string
    {
        return EventPlannerModule::getValues($this->assignedModule);
    }


}