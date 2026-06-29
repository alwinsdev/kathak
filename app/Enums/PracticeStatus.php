<?php

declare(strict_types=1);

namespace App\Enums;

enum PracticeStatus: string
{
    case InProgress = 'in_progress';
    case Verified = 'verified';
    case Abandoned = 'abandoned';

    public function label(): string
    {
        return match ($this) {
            self::InProgress => 'In progress',
            self::Verified => 'Verified',
            self::Abandoned => 'Abandoned',
        };
    }
}
