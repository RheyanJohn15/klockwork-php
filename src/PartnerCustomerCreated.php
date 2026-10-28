<?php

declare(strict_types=1);

namespace Klockwork;

final class PartnerCustomerCreated
{
    public function __construct(
        public readonly PartnerCustomer $customer,
        public readonly string $inviteUrl,
        public readonly bool $invitationSent,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload, int $status = 201): self
    {
        return new self(
            PartnerCustomer::fromArray(
                PartnerPayload::nestedArray($payload, 'customer', $status, 'Invalid partner customer create response'),
                $status,
            ),
            PartnerPayload::string($payload, 'inviteUrl', $status),
            PartnerPayload::bool($payload, 'invitationSent', $status),
        );
    }
}
