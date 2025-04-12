<?php

namespace App\Entity\UserModule;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'administrator')]
class Administrator extends User
{

    public function getRoles(): array
    {
        return ['ROLE_ADMIN'];
    }

}