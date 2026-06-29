<?php

declare(strict_types=1);

namespace App\Domain\AI\Exceptions;

use RuntimeException;

/**
 * Raised when the inference provider is unreachable, misconfigured, or returns
 * an error. Carries technical detail for logs; controllers translate it into a
 * safe, generic user-facing message.
 */
class InferenceException extends RuntimeException {}
