<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domain\AI\Services\PracticeSessionService;
use App\Enums\PracticeStatus;
use App\Events\PracticeVerified;
use App\Models\PracticeSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class PracticeSessionServiceTest extends TestCase
{
    use RefreshDatabase;

    private PracticeSessionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(PracticeSessionService::class);
    }

    public function test_complete_verifies_once_and_dispatches_event_once(): void
    {
        Event::fake([PracticeVerified::class]);
        $session = PracticeSession::factory()->create(['status' => PracticeStatus::InProgress]);

        $first = $this->service->complete($session, 'Pataka', 0.93, 'corr-1');
        $second = $this->service->complete($session, 'Pataka', 0.93, 'corr-1');

        $this->assertTrue($first);
        $this->assertFalse($second); // idempotent: already verified

        $this->assertDatabaseHas('practice_sessions', [
            'id' => $session->id,
            'status' => PracticeStatus::Verified->value,
            'detected_class' => 'Pataka',
        ]);

        Event::assertDispatchedTimes(PracticeVerified::class, 1);
    }
}
