<?php

namespace App\Entity\UserModule;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'administrator')]
class Administrator extends User
{

    // Add specific fields for Administrator if needed


}