<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Actions\RegisterPatient;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterPatientRequest;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the patient registration view with the list of doctors to pick from.
     */
    public function create(): View
    {
        $doctors = User::doctors()->orderBy('name')->get(['id', 'name']);

        return view('auth.register', compact('doctors'));
    }

    /**
     * Handle an incoming patient registration request.
     */
    public function store(RegisterPatientRequest $request, RegisterPatient $registerPatient): RedirectResponse
    {
        $user = $registerPatient->handle($request->validated());

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}
