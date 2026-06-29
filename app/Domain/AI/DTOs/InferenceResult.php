<?php

declare(strict_types=1);

namespace App\Domain\AI\DTOs;

/**
 * The normalised result of one inference call.
 */
readonly class InferenceResult
{
    /**
     * @param  list<MudraPrediction>  $predictions
     */
    public function __construct(public array $predictions) {}

    public function topPrediction(): ?MudraPrediction
    {
        $top = null;
        foreach ($this->predictions as $prediction) {
            if ($top === null || $prediction->confidence > $top->confidence) {
                $top = $prediction;
            }
        }

        return $top;
    }

    public function topClass(): ?string
    {
        return $this->topPrediction()?->class;
    }

    /**
     * Highest confidence among predictions whose class matches $class
     * (trimmed, case-insensitive). 0.0 if the class was not detected.
     */
    public function confidenceFor(string $class): float
    {
        $needle = mb_strtolower(trim($class));
        $best = 0.0;

        foreach ($this->predictions as $prediction) {
            if (mb_strtolower(trim($prediction->class)) === $needle) {
                $best = max($best, $prediction->confidence);
            }
        }

        return $best;
    }
}
