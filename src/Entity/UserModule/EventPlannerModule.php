<?php

namespace App\Entity\UserModule;

use MyCLabs\Enum\Enum;


enum EventPlannerModule: string
{
    case LOGISTICS = 'LOGISTICS';
    case RESSOURCES = 'RESSOURCES';
    case WORKSHOPS = 'WORKSHOPS';
    case SCHEDULE = 'SCHEDULE';
    public static function getValues(self $module): string
    {
        return match($module) {
            self::LOGISTICS => 'Logistics',
            self::RESSOURCES => 'Resources',
            self::WORKSHOPS => 'Workshops',
            self::SCHEDULE => 'Schedule',
        };
    }
    public static function getChoices(): array
    {
        return [
            'Logistics' => self::LOGISTICS,
            'Resources' => self::RESSOURCES,
            'Workshops' => self::WORKSHOPS,
            'Schedule' => self::SCHEDULE,
        ];
    }
}