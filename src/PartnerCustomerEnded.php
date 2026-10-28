<?php

declare(strict_types=1);

namespace Klockwork;

final class PartnerCustomerEnded
{
    public function __construct(
        public readonly bool $ok,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload, int $status = 200): self
    {
        return new self(PartnerPayload::bool($payload, 'ok', $status));
    }
}
