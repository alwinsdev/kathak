<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_redirects_doctor_to_doctor_home(): void
    {
        $doctor = User::factory()->doctor()->create();

        $this->actingAs($doctor)
            ->get('/dashboard')
            ->assertRedirect(route('doctor.dashboard'));
    }

    public function test_dashboard_redirects_patient_to_patient_home(): void
    {
        $patient = User::factory()->create();

        $this->actingAs($patient)
            ->get('/dashboard')
            ->assertRedirect(route('patient.dashboard'));
    }

    public function test_patient_cannot_access_doctor_area(): void
    {
        $patient = User::factory()->create();

        $this->actingAs($patient)
            ->get('/doctor/dashboard')
            ->assertForbidden();
    }

    public function test_doctor_cannot_access_patient_area(): void
    {
        $doctor = User::factory()->doctor()->create();

        $this->actingAs($doctor)
            ->get('/patient/dashboard')
            ->assertForbidden();
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/doctor/dashboard')->assertRedirect(route('login'));
        $this->get('/patient/dashboard')->assertRedirect(route('login'));
    }
}
