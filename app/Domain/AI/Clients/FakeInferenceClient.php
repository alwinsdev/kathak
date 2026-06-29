<?php

declare(strict_types=1);

namespace App\Domain\AI\Clients;

use App\Domain\AI\Contracts\InferenceClient;
use App\Domain\AI\DTOs\InferenceResult;
use App\Domain\AI\DTOs\MudraPrediction;

/**
 * Deterministic inference client for local development and automated tests.
 * Returns whatever predictions it was primed with — no network calls.
 */
class FakeInferenceClient implements InferenceClient
{
    private InferenceResult $next;

    public function __construct()
    {
        $this->next = new InferenceResult([]);
    }

    public function detect(string $imageBinary): InferenceResult
    {
        return $this->next;
    }

    public function withResult(InferenceResult $result): self
    {
        $this->next = $result;

        return $this;
    }

    public function withPredictions(MudraPrediction ...$predictions): self
    {
        $this->next = new InferenceResult(array_values($predictions));

        return $this;
    }

    /** Convenience: prime a single detection of $class at $confidence. */
    public function withDetection(string $class, float $confidence): self
    {
        return $this->withPredictions(new MudraPrediction($class, $confidence, 100, 100, 80, 80));
    }

    /** Prime an empty result (no mudra detected). */
    public function withNothing(): self
    {
        $this->next = new InferenceResult([]);

        return $this;
    }
}
