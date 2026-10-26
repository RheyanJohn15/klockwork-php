<?php

declare(strict_types=1);

namespace Klockwork\Resources;

use Klockwork\HttpClient;
use Klockwork\Subject;

final class CompanyApi
{
    public readonly CompanyInvoices $invoices;

    public readonly CompanyTalents $talents;

    public readonly CompanyTime $time;

    public function __construct(
        private readonly HttpClient $http,
        ?string $boundClientId = null,
    ) {
        $clientOf = static function (?string $explicit) use ($boundClientId): string {
            return Subject::requireClient($explicit, $boundClientId);
        };
        $root = '/api/public/company';
        $this->invoices = new CompanyInvoices($http, $clientOf, $root);
        $this->talents = new CompanyTalents($http, $clientOf, $root);
        $this->time = new CompanyTime($http, $clientOf, $root);
    }

    public function forClient(string $clientId): self
    {
        return new self($this->http, $clientId);
    }
}

final class CompanyInvoices
{
    /**
     * @param \Closure(?string): string $clientOf
     */
    public function __construct(
        private readonly HttpClient $http,
        private readonly \Closure $clientOf,
        private readonly string $root,
    ) {
    }

    /**
     * @param array{clientId?: string}|null $options
     */
    public function list(?array $options = null): mixed
    {
        return $this->http->request('GET', $this->root . '/invoices', [
            'clientId' => ($this->clientOf)($this->stringOrNull($options['clientId'] ?? null)),
        ]);
    }

    /**
     * @param array{clientId?: string}|null $options
     */
    public function get(string $id, ?array $options = null): mixed
    {
        return $this->http->request('GET', $this->root . '/invoices/' . Subject::segment($id), [
            'clientId' => ($this->clientOf)($this->stringOrNull($options['clientId'] ?? null)),
        ]);
    }

    private function stringOrNull(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        if (!is_string($value)) {
            throw new \InvalidArgumentException('clientId must be a string.');
        }

        return $value;
    }
}

final class CompanyTalents
{
    /**
     * @param \Closure(?string): string $clientOf
     */
    public function __construct(
        private readonly HttpClient $http,
        private readonly \Closure $clientOf,
        private readonly string $root,
    ) {
    }

    /**
     * @param array{clientId?: string}|null $options
     */
    public function list(?array $options = null): mixed
    {
        $clientId = $options['clientId'] ?? null;
        if ($clientId !== null && !is_string($clientId)) {
            throw new \InvalidArgumentException('clientId must be a string.');
        }

        return $this->http->request('GET', $this->root . '/talents', [
            'clientId' => ($this->clientOf)($clientId),
        ]);
    }
}

final class CompanyTime
{
    /**
     * @param \Closure(?string): string $clientOf
     */
    public function __construct(
        private readonly HttpClient $http,
        private readonly \Closure $clientOf,
        private readonly string $root,
    ) {
    }

    /**
     * @param array<string, mixed> $query
     */
    public function list(array $query): mixed
    {
        ['clientId' => $clientId, 'rest' => $rest] = Subject::splitClient($query);

        return $this->http->request('GET', $this->root . '/time', [
            'query' => $rest,
            'clientId' => ($this->clientOf)($clientId),
        ]);
    }
}
