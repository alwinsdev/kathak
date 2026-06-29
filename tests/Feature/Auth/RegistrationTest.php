<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Enums\Gender;
use App\Enums\Role;
use App\Models\PatientProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        User::factory()->doctor()->create();

        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_patients_can_register_and_are_linked_to_a_doctor(): void
    {
        $doctor = User::factory()->doctor()->create();

        $response = $this->post('/register', [
            'name' => 'Test Patient',
            'email' => 'patient@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'doctor_id' => $doctor->id,
            'age' => 40,
            'gender' => Gender::Female->value,
            'phone' => '9876543210',
            'condition_notes' => 'Arthritis.',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));

        $user = User::where('email', 'patient@example.com')->firstOrFail();
        $this->assertSame(Role::Patient, $user->role);

        $this->assertDatabaseHas('patient_profiles', [
            'user_id' => $user->id,
            'doctor_id' => $doctor->id,
            'age' => 40,
        ]);
    }

    public function test_registration_requires_a_valid_doctor(): void
    {
        $response = $this->from('/register')->post('/register', [
            'name' => 'Test Patient',
            'email' => 'patient@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'doctor_id' => 999, // does not exist
        ]);

        $response->assertSessionHasErrors('doctor_id');
        $this->assertGuest();
        $this->assertDatabaseCount(PatientProfile::class, 0);
    }
}
