<?php

declare(strict_types=1);

namespace App\Enum;

enum TaskDifficulty: string
{
    case EASY = 'EASY';
    case MEDIUM = 'MEDIUM';
    case HARD = 'HARD';

    public function getLabel(): string
    {
        return match ($this) {
            self::EASY => 'tickets.form_task_easy',
            self::MEDIUM => 'tickets.form_task_medium',
            self::HARD => 'tickets.form_task_hard',
        };
    }

    public function getBadgeClass(): string
    {
        return match ($this) {
            self::EASY => 'success',
            self::MEDIUM => 'warning',
            self::HARD => 'danger',
        };
    }
}
