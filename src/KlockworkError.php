<?php

declare(strict_types=1);

namespace Klockwork;

final class KlockworkError extends \RuntimeException
{
    public function __construct(
        public readonly int $status,
        public readonly string $error,
        public readonly mixed $body,
        public readonly mixed $details = null,
    ) {
        parent::__construct($error);
    }
}
