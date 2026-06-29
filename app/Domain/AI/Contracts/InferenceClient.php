<?php

declare(strict_types=1);

namespace App\Domain\AI\Contracts;

use App\Domain\AI\DTOs\InferenceResult;
use App\Domain\AI\Exceptions\InferenceException;

interface InferenceClient
{
    /**
     * Run mudra inference on a raw image (binary contents of one frame).
     *
     * @throws InferenceException when the inference service is unavailable or errors.
     */
    public function detect(string $imageBinary): InferenceResult;
}
