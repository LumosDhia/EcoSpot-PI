<?php

declare(strict_types=1);

namespace App\Enum;

enum TicketPriority: string
{
    case LOW = 'LOW';
    case MEDIUM = 'MEDIUM';
    case HIGH = 'HIGH';
    case URGENT = 'URGENT';

    public function getLabel(): string
    {
        return match ($this) {
            self::LOW => 'Low',
            self::MEDIUM => 'Medium',
            self::HIGH => 'High',
            self::URGENT => 'Urgent',
        };
    }

    public function getBadgeClass(): string
    {
        return match ($this) {
            self::LOW => 'secondary',
            self::MEDIUM => 'info',
            self::HIGH => 'warning',
            self::URGENT => 'danger',
        };
    }
}
