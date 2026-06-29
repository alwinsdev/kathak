<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PrescriptionStatus;
use Database\Factories\PrescriptionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Prescription extends Model
{
    /** @use HasFactory<PrescriptionFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'patient_id',
        'doctor_id',
        'mudra_id',
        'scheduled_time',
        'duration_min',
        'start_date',
        'notes',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'duration_min' => 'integer',
            'status' => PrescriptionStatus::class,
        ];
    }

    public function isActive(): bool
    {
        return $this->status === PrescriptionStatus::Active;
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'patient_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    /**
     * @return BelongsTo<Mudra, $this>
     */
    public function mudra(): BelongsTo
    {
        return $this->belongsTo(Mudra::class);
    }

    /**
     * @param  Builder<Prescription>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('status', PrescriptionStatus::Active);
    }
}
