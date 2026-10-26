<?php

declare(strict_types=1);

namespace Klockwork;

final class TransportResponse
{
    public function __construct(
        public readonly int $status,
        public readonly string $statusText,
        public readonly string $body,
    ) {
    }
}
