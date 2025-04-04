<?php

namespace App\DBAL\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

class EnumType extends Type
{
    const ENUM = 'enum';

    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform): string
    {
        return "ENUM('value1', 'value2', 'value3')"; // Adjust the values as per your enum definition
    }

    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        return $value;
    }

    public function getName(): string
    {
        return self::ENUM;
    }
}