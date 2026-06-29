<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Role;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => Role::class,
        ];
    }

    /**
     * The demographic profile for a patient user.
     *
     * @return HasOne<PatientProfile, $this>
     */
    public function patientProfile(): HasOne
    {
        return $this->hasOne(PatientProfile::class);
    }

    /**
     * Patient profiles owned by this doctor (the doctor's panel).
     *
     * @return HasMany<PatientProfile, $this>
     */
    public function assignedPatients(): HasMany
    {
        return $this->hasMany(PatientProfile::class, 'doctor_id');
    }

    /**
     * Prescriptions written for this user as a patient.
     *
     * @return HasMany<Prescription, $this>
     */
    public function prescriptions(): HasMany
    {
        return $this->hasMany(Prescription::class, 'patient_id');
    }

    /**
     * Practice sessions performed by this user as a patient.
     *
     * @return HasMany<PracticeSession, $this>
     */
    public function practiceSessions(): HasMany
    {
        return $this->hasMany(PracticeSession::class, 'patient_id');
    }

    public function isDoctor(): bool
    {
        return $this->role === Role::Doctor;
    }

    public function isPatient(): bool
    {
        return $this->role === Role::Patient;
    }

    /**
     * @param  Builder<User>  $query
     */
    public function scopeDoctors(Builder $query): void
    {
        $query->where('role', Role::Doctor);
    }

    /**
     * @param  Builder<User>  $query
     */
    public function scopePatients(Builder $query): void
    {
        $query->where('role', Role::Patient);
    }
}
