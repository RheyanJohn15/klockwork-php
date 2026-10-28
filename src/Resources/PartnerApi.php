<?php

declare(strict_types=1);

namespace Klockwork\Resources;

use Klockwork\HttpClient;
use Klockwork\PartnerCustomer;
use Klockwork\PartnerCustomerCreated;
use Klockwork\PartnerCustomerEnded;
use Klockwork\PartnerMe;
use Klockwork\PartnerPayload;
use Klockwork\Subject;

/**
 * Partner-key surface for `/api/public/partner/*`.
 *
 * Use a partner `kw_` key only. Do not pass a partner key to agency, solo, or company methods.
 * Identity comes from the hashed key row on the API. This client does not invent JWT helpers.
 */
final class PartnerApi
{
    public readonly PartnerCustomers $customers;

    public function __construct(private readonly HttpClient $http)
    {
        $root = '/api/public/partner';
        $this->customers = new PartnerCustomers($http, $root);
    }

    /**
     * Requires scope `status:read`.
     */
    public function me(): PartnerMe
    {
        $payload = PartnerPayload::requireArray(
            $this->http->request('GET', '/api/public/partner/me'),
            200,
            'Invalid partner me response',
        );

        return PartnerMe::fromArray($payload);
    }
}

final class PartnerCustomers
{
    public function __construct(
        private readonly HttpClient $http,
        private readonly string $root,
    ) {
    }

    /**
     * Requires scope `customers:read`.
     *
     * @return list<PartnerCustomer>
     */
    public function list(): array
    {
        $payload = PartnerPayload::requireArray(
            $this->http->request('GET', $this->root . '/customers'),
            200,
            'Invalid partner customers response',
        );

        $customers = [];
        foreach (PartnerPayload::listOfArrays($payload, 'customers', 200, 'Invalid partner customers response') as $item) {
            $customers[] = PartnerCustomer::fromArray($item);
        }

        return $customers;
    }

    /**
     * Requires scope `customers:write`.
     */
    public function create(string $companyName, string $invoiceEmail): PartnerCustomerCreated
    {
        $payload = PartnerPayload::requireArray(
            $this->http->request('POST', $this->root . '/customers', [
                'body' => [
                    'companyName' => $companyName,
                    'invoiceEmail' => $invoiceEmail,
                ],
            ]),
            201,
            'Invalid partner customer create response',
        );

        return PartnerCustomerCreated::fromArray($payload);
    }

    /**
     * Requires scope `customers:write`. No request body.
     */
    public function end(string $id): PartnerCustomerEnded
    {
        $payload = PartnerPayload::requireArray(
            $this->http->request('POST', $this->root . '/customers/' . Subject::segment($id) . '/end'),
            200,
            'Invalid partner customer end response',
        );

        return PartnerCustomerEnded::fromArray($payload);
    }
}
