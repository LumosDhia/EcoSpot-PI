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
            self::WASTE => 'Waste',
            self::POLLUTION => 'Pollution',
            self::WATER => 'Water',
            self::AIR => 'Air',
            self::GREEN_SPACES => 'Green spaces',
            self::FAUNA_FLORA => 'Fauna and flora',
            self::OTHER => 'Other',
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
