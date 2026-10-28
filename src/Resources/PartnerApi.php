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
 * Partner-key surface for `/api/public/partner/*` and product calls via `X-Klockwork-Customer`.
 *
 * Relationship methods use the partner key alone. Product calls (agency, solo, company) need
 * `X-Klockwork-Customer` via `forCustomer()` or the constructor `customerId` option.
 * For white-label, that value is usually the partner company's own Clerk org id (`PartnerMe::$orgId`
 * / Partnership → Status). You may also send an accepted covered customer's org id or
 * `partner_customers.id` when operating that customer's book.
 * Identity comes from the hashed key row and the org (or customer relationship) on the API.
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
     * Call agency, solo, and company APIs with the partner key plus `X-Klockwork-Customer`.
     *
     * `$customerId` is usually the partner company's own Clerk org id (`PartnerMe::$orgId` /
     * Partnership → Status “Company org id”). You may also pass an accepted covered customer's
     * Clerk org id (`org_…`) or `partner_customers.id`.
     * Requires `agency:*`, `solo:*`, and/or `company:read` scopes on the key.
     */
    public function forCustomer(string $customerId): PartnerCustomerContext
    {
        return new PartnerCustomerContext($this->http->withCustomer($customerId));
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

/**
 * Agency, solo, and company clients bound to a partner key plus `X-Klockwork-Customer`.
 */
final class PartnerCustomerContext
{
    public readonly AgencyApi $agency;

    public readonly SoloApi $solo;

    public readonly CompanyApi $company;

    public function __construct(HttpClient $http)
    {
        $this->agency = new AgencyApi($http);
        $this->solo = new SoloApi($http);
        $this->company = new CompanyApi($http);
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
