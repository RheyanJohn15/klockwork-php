<?php

declare(strict_types=1);

namespace Klockwork\Resources;

use Klockwork\HttpClient;
use Klockwork\Subject;

final class PartnerApi
{
    public readonly PartnerCustomers $customers;

    public function __construct(private readonly HttpClient $http)
    {
        $this->customers = new PartnerCustomers($http, '/api/public/partner');
    }

    public function me(): mixed
    {
        return $this->http->request('GET', '/api/public/partner/me');
    }
}

final class PartnerCustomers
{
    public function __construct(
        private readonly HttpClient $http,
        private readonly string $root,
    ) {
    }

    public function list(): mixed
    {
        return $this->http->request('GET', $this->root . '/customers');
    }

    /**
     * @param array<string, mixed> $input
     */
    public function create(array $input): mixed
    {
        return $this->http->request('POST', $this->root . '/customers', ['body' => $input]);
    }

    public function end(string $id): mixed
    {
        return $this->http->request('POST', $this->root . '/customers/' . Subject::segment($id) . '/end');
    }
}
