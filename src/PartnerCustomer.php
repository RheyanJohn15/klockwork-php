<?php

declare(strict_types=1);

namespace Klockwork;

final class PartnerCustomer
{
    public function __construct(
        public readonly string $id,
        public readonly string $companyName,
        public readonly string $invoiceEmail,
        public readonly string $status,
        public readonly ?string $customerClerkOrgId,
        public readonly ?string $associatedAt,
        public readonly ?string $inviteUrl,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload, int $status = 200): self
    {
        return new self(
            PartnerPayload::string($payload, 'id', $status),
            PartnerPayload::string($payload, 'companyName', $status),
            PartnerPayload::string($payload, 'invoiceEmail', $status),
            PartnerPayload::string($payload, 'status', $status),
            PartnerPayload::nullableString($payload, 'customerClerkOrgId', $status),
            PartnerPayload::nullableString($payload, 'associatedAt', $status),
            PartnerPayload::optionalNullableString($payload, 'inviteUrl', $status),
        );
    }
}
