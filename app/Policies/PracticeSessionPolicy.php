<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\PracticeSession;
use App\Models\User;

class PracticeSessionPolicy
{
    /**
     * A patient may act on (detect against / abandon) only their own session.
     */
    public function update(User $user, PracticeSession $session): bool
    {
        return $user->isPatient() && $session->patient_id === $user->id;
    }
}
