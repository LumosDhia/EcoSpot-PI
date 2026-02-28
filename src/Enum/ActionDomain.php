<?php

declare(strict_types=1);

namespace App\Enum;

enum ActionDomain: string
{
    case WASTE = 'WASTE';
    case POLLUTION = 'POLLUTION';
    case WATER = 'WATER';
    case AIR = 'AIR';
    case GREEN_SPACES = 'GREEN_SPACES';
    case FAUNA_FLORA = 'FAUNA_FLORA';
    case OTHER = 'OTHER';

    public function getLabel(): string
    {
        return match ($this) {
            self::WASTE => 'tickets.form_domain_waste',
            self::POLLUTION => 'tickets.form_domain_pollution',
            self::WATER => 'tickets.form_domain_water',
            self::AIR => 'tickets.form_domain_air',
            self::GREEN_SPACES => 'tickets.form_domain_green_spaces',
            self::FAUNA_FLORA => 'tickets.form_domain_fauna_flora',
            self::OTHER => 'tickets.form_domain_other',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::WASTE => 'bi-trash',
            self::POLLUTION => 'bi-exclamation-triangle',
            self::WATER => 'bi-droplet',
            self::AIR => 'bi-wind',
            self::GREEN_SPACES => 'bi-tree',
            self::FAUNA_FLORA => 'bi-flower1',
            self::OTHER => 'bi-question-circle',
        };
    }
}
