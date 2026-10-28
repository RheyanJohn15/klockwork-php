<?php

declare(strict_types=1);

namespace Klockwork;

final class PartnerMe
{
    public function __construct(
        public readonly string $partnerId,
        public readonly string $status,
        public readonly bool $subscriptionExempt,
        /** Partner company Clerk org id (`org_…`). Use as `customerId` / `X-Klockwork-Customer` for white-label product calls. */
        public readonly string $orgId,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload, int $status = 200): self
    {
        return new self(
            PartnerPayload::string($payload, 'partnerId', $status),
            PartnerPayload::string($payload, 'status', $status),
            PartnerPayload::bool($payload, 'subscriptionExempt', $status),
            PartnerPayload::string($payload, 'orgId', $status),
        );
    }
}
