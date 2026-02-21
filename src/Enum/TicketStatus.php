<?php

declare(strict_types=1);

namespace App\Enum;

enum TicketStatus: string
{
    case PENDING = 'PENDING';
    case SENT_BACK = 'SENT_BACK';
    case REFUSED = 'REFUSED';
    case PUBLISHED = 'PUBLISHED';

    public function getLabel(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::SENT_BACK => 'Sent back for modification',
            self::REFUSED => 'Refused',
            self::PUBLISHED => 'Published',
        };
    }

    public function getBadgeClass(): string
    {
        return match ($this) {
            self::PENDING => 'warning',
            self::SENT_BACK => 'info',
            self::REFUSED => 'danger',
            self::PUBLISHED => 'success',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::PENDING => 'fas fa-hourglass-half',
            self::SENT_BACK => 'fas fa-undo-alt',
            self::REFUSED => 'fas fa-times-circle',
            self::PUBLISHED => 'fas fa-check-circle',
        };
    }
}
