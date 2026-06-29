<?php

declare(strict_types=1);

namespace App\Enums;

enum Role: string
{
    case Doctor = 'doctor';
    case Patient = 'patient';

    public function label(): string
    {
        return match ($this) {
            self::Doctor => 'Doctor',
            self::Patient => 'Patient',
        };
    }
}
