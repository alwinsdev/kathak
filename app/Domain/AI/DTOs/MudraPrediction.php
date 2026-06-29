<?php

declare(strict_types=1);

namespace App\Domain\AI\DTOs;

/**
 * A single prediction from the inference provider. Bounding-box fields are
 * nullable to support classification-only models.
 */
readonly class MudraPrediction
{
    public function __construct(
        public string $class,
        public float $confidence,
        public ?float $x = null,
        public ?float $y = null,
        public ?float $width = null,
        public ?float $height = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'class' => $this->class,
            'confidence' => $this->confidence,
            'x' => $this->x,
            'y' => $this->y,
            'width' => $this->width,
            'height' => $this->height,
        ];
    }
}
