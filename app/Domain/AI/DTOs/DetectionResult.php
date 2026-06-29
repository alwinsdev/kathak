<?php

declare(strict_types=1);

namespace App\Domain\AI\DTOs;

/**
 * The per-frame evaluation outcome produced by VerifyPracticeAction.
 *
 * This carries the pure evaluation only — hold progress and verification are
 * decided downstream (PracticeHoldTracker / PracticeSessionService) and merged
 * into the HTTP response by the controller.
 */
readonly class DetectionResult
{
    /**
     * @param  list<MudraPrediction>  $predictions
     */
    public function __construct(
        public bool $matched,
        public float $confidence,
        public ?string $detectedClass,
        public array $predictions,
        public int $processingMs,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'matched' => $this->matched,
            'confidence' => $this->confidence,
            'detected_class' => $this->detectedClass,
            'processing_time_ms' => $this->processingMs,
            'predictions' => array_map(fn (MudraPrediction $p) => $p->toArray(), $this->predictions),
        ];
    }
}
