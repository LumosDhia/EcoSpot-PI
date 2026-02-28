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
            self::LOW => 'tickets.form_priority_low',
            self::MEDIUM => 'tickets.form_priority_medium',
            self::HIGH => 'tickets.form_priority_high',
            self::URGENT => 'tickets.form_priority_urgent',
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
