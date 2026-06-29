<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Prescription;
use Illuminate\Foundation\Events\Dispatchable;

class PrescriptionCreated
{
    use Dispatchable;

    public function __construct(public readonly Prescription $prescription) {}
}
