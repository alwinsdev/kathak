<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PrescriptionStatus;
use Database\Factories\PrescriptionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'end_date',
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
            'end_date' => 'date',
            'duration_min' => 'integer',
            'status' => PrescriptionStatus::class,
        ];
    }

    public function isActive(): bool
    {
        return $this->status === PrescriptionStatus::Active;
    }

    /**
     * The verification hold time for practice sessions (global config default).
     */
    public function holdSeconds(): int
    {
        return (int) config('practice.hold_seconds');
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

    /**
     * Prescriptions that are in effect on the given date: active, started, and
     * either open-ended or not yet past their end date.
     *
     * @param  Builder<Prescription>  $query
     */
    public function scopeActiveOn(Builder $query, \DateTimeInterface|string $date): void
    {
        $query->where('status', PrescriptionStatus::Active)
            ->whereDate('start_date', '<=', $date)
            ->where(function (Builder $q) use ($date) {
                $q->whereNull('end_date')->orWhereDate('end_date', '>=', $date);
            });
    }

    /**
     * Practice sessions recorded against this prescription.
     *
     * @return HasMany<PracticeSession, $this>
     */
    public function practiceSessions(): HasMany
    {
        return $this->hasMany(PracticeSession::class);
    }
}
