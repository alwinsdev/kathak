<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PracticeStatus;
use Database\Factories\PracticeSessionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PracticeSession extends Model
{
    /** @use HasFactory<PracticeSessionFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'prescription_id',
        'patient_id',
        'practiced_on',
        'started_at',
        'completed_at',
        'status',
        'best_confidence',
        'detected_class',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'practiced_on' => 'date',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'status' => PracticeStatus::class,
            'best_confidence' => 'float',
        ];
    }

    /**
     * @return BelongsTo<Prescription, $this>
     */
    public function prescription(): BelongsTo
    {
        return $this->belongsTo(Prescription::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'patient_id');
    }

    /**
     * @param  Builder<PracticeSession>  $query
     */
    public function scopeVerified(Builder $query): void
    {
        $query->where('status', PracticeStatus::Verified);
    }
}
