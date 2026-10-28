<?php

declare(strict_types=1);

namespace Klockwork;

use Klockwork\Resources\AgencyApi;
use Klockwork\Resources\CompanyApi;
use Klockwork\Resources\PartnerApi;
use Klockwork\Resources\SoloApi;

final class Klockwork
{
    public const PRODUCTION_BASE_URL = 'https://api.klockwork.org';

    public const LOCAL_BASE_URL = 'http://localhost:3001';

    public readonly AgencyApi $agency;

    public readonly SoloApi $solo;

    public readonly CompanyApi $company;

    public readonly PartnerApi $partner;

    /**
     * @param array{
     *     apiKey: string,
     *     environment?: 'production'|'local',
     *     baseUrl?: string,
     *     talentId?: string,
     *     clientId?: string,
     *     transport?: callable
     * } $options
     */
    public function __construct(array $options)
    {
        $apiKey = Subject::assertApiKey((string) ($options['apiKey'] ?? ''));
        $transport = $options['transport'] ?? new CurlTransport();
        if (!is_callable($transport)) {
            throw new \InvalidArgumentException('Klockwork transport must be callable.');
        }

        $http = new HttpClient($apiKey, self::resolveBaseUrl($options), $transport);
        $this->agency = new AgencyApi($http);
        $this->solo = new SoloApi($http, self::optionalString($options['talentId'] ?? null));
        $this->company = new CompanyApi($http, self::optionalString($options['clientId'] ?? null));
        $this->partner = new PartnerApi($http);
    }

    /**
     * @param array{environment?: string, baseUrl?: string} $options
     */
    public static function resolveBaseUrl(array $options): string
    {
        $explicit = trim((string) ($options['baseUrl'] ?? ''));
        $raw = $explicit !== ''
            ? $explicit
            : (($options['environment'] ?? null) === 'local' ? self::LOCAL_BASE_URL : self::PRODUCTION_BASE_URL);

        return rtrim($raw, '/');
    }

    private static function optionalString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        if (!is_string($value)) {
            throw new \InvalidArgumentException('talentId and clientId must be strings.');
        }

        return $value;
    }
}
