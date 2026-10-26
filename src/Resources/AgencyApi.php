<?php

declare(strict_types=1);

namespace Klockwork\Resources;

use Klockwork\HttpClient;
use Klockwork\Subject;

final class AgencyApi
{
    public readonly AgencyClients $clients;

    public readonly AgencyTalents $talents;

    public readonly AgencyAssignments $assignments;

    public readonly AgencyTime $time;

    public readonly AgencyInvoices $invoices;

    public readonly AgencyPayments $payments;

    public readonly AgencyBusinessProfile $businessProfile;

    public readonly AgencyPreferences $preferences;

    public readonly AgencyNotificationSettings $notificationSettings;

    public readonly AgencyAccess $access;

    public readonly AgencyClientInvites $clientInvites;

    public readonly AgencyPartner $partner;

    public function __construct(HttpClient $http)
    {
        $root = '/api/public/agency';
        $this->clients = new AgencyClients($http, $root);
        $this->talents = new AgencyTalents($http, $root);
        $this->assignments = new AgencyAssignments($http, $root);
        $this->time = new AgencyTime($http, $root);
        $this->invoices = new AgencyInvoices($http, $root);
        $this->payments = new AgencyPayments($http, $root);
        $this->businessProfile = new AgencyBusinessProfile($http, $root);
        $this->preferences = new AgencyPreferences($http, $root);
        $this->notificationSettings = new AgencyNotificationSettings($http, $root);
        $this->access = new AgencyAccess($http, $root);
        $this->clientInvites = new AgencyClientInvites($http, $root);
        $this->partner = new AgencyPartner($http, $root);
    }
}

final class AgencyClients
{
    public function __construct(
        private readonly HttpClient $http,
        private readonly string $root,
    ) {
    }

    /**
     * @param array<string, mixed>|null $query
     */
    public function list(?array $query = null): mixed
    {
        return $this->http->request('GET', $this->root . '/clients', ['query' => $query]);
    }

    /**
     * @param array<string, mixed> $input
     */
    public function create(array $input): mixed
    {
        return $this->http->request('POST', $this->root . '/clients', ['body' => $input]);
    }

    public function get(string $id): mixed
    {
        return $this->http->request('GET', $this->root . '/clients/' . Subject::segment($id));
    }

    /**
     * @param array<string, mixed> $input
     */
    public function update(string $id, array $input): mixed
    {
        return $this->http->request('PATCH', $this->root . '/clients/' . Subject::segment($id), ['body' => $input]);
    }

    public function remove(string $id): mixed
    {
        return $this->http->request('DELETE', $this->root . '/clients/' . Subject::segment($id));
    }

    public function invite(string $id): mixed
    {
        return $this->http->request('POST', $this->root . '/clients/' . Subject::segment($id) . '/invite');
    }

    public function revokeInvite(string $id): mixed
    {
        return $this->http->request('POST', $this->root . '/clients/' . Subject::segment($id) . '/invite/revoke');
    }

    public function cover(string $id): mixed
    {
        return $this->http->request('POST', $this->root . '/clients/' . Subject::segment($id) . '/cover');
    }
}

final class AgencyTalents
{
    public function __construct(
        private readonly HttpClient $http,
        private readonly string $root,
    ) {
    }

    /**
     * @param array<string, mixed>|null $query
     */
    public function list(?array $query = null): mixed
    {
        return $this->http->request('GET', $this->root . '/talents', ['query' => $query]);
    }

    /**
     * @param array<string, mixed> $input
     */
    public function create(array $input): mixed
    {
        return $this->http->request('POST', $this->root . '/talents', ['body' => $input]);
    }

    public function get(string $id): mixed
    {
        return $this->http->request('GET', $this->root . '/talents/' . Subject::segment($id));
    }

    /**
     * @param array<string, mixed> $input
     */
    public function update(string $id, array $input): mixed
    {
        return $this->http->request('PATCH', $this->root . '/talents/' . Subject::segment($id), ['body' => $input]);
    }

    public function remove(string $id): mixed
    {
        return $this->http->request('DELETE', $this->root . '/talents/' . Subject::segment($id));
    }

    public function invite(string $id): mixed
    {
        return $this->http->request('POST', $this->root . '/talents/' . Subject::segment($id) . '/invite');
    }
}

final class AgencyAssignments
{
    public function __construct(
        private readonly HttpClient $http,
        private readonly string $root,
    ) {
    }

    /**
     * @param array<string, mixed>|null $query
     */
    public function list(?array $query = null): mixed
    {
        return $this->http->request('GET', $this->root . '/assignments', ['query' => $query]);
    }

    /**
     * @param array<string, mixed> $input
     */
    public function create(array $input): mixed
    {
        return $this->http->request('POST', $this->root . '/assignments', ['body' => $input]);
    }

    public function get(string $id): mixed
    {
        return $this->http->request('GET', $this->root . '/assignments/' . Subject::segment($id));
    }

    /**
     * @param array<string, mixed> $input
     */
    public function update(string $id, array $input): mixed
    {
        return $this->http->request('PATCH', $this->root . '/assignments/' . Subject::segment($id), ['body' => $input]);
    }
}

final class AgencyTime
{
    public function __construct(
        private readonly HttpClient $http,
        private readonly string $root,
    ) {
    }

    /**
     * @param array<string, mixed> $query
     */
    public function list(array $query): mixed
    {
        return $this->http->request('GET', $this->root . '/time', ['query' => $query]);
    }

    /**
     * @param array<string, mixed> $input
     */
    public function create(array $input): mixed
    {
        return $this->http->request('POST', $this->root . '/time', ['body' => $input]);
    }
}

final class AgencyInvoices
{
    public function __construct(
        private readonly HttpClient $http,
        private readonly string $root,
    ) {
    }

    public function list(): mixed
    {
        return $this->http->request('GET', $this->root . '/invoices');
    }

    /**
     * @param array<string, mixed> $input
     */
    public function create(array $input): mixed
    {
        return $this->http->request('POST', $this->root . '/invoices', ['body' => $input]);
    }

    public function get(string $id): mixed
    {
        return $this->http->request('GET', $this->root . '/invoices/' . Subject::segment($id));
    }

    public function voidInvoice(string $id): mixed
    {
        return $this->http->request('POST', $this->root . '/invoices/' . Subject::segment($id) . '/void');
    }
}

final class AgencyPayments
{
    public function __construct(
        private readonly HttpClient $http,
        private readonly string $root,
    ) {
    }

    public function list(): mixed
    {
        return $this->http->request('GET', $this->root . '/payments');
    }

    /**
     * @param array<string, mixed> $input
     */
    public function create(array $input): mixed
    {
        return $this->http->request('POST', $this->root . '/payments', ['body' => $input]);
    }
}

final class AgencyBusinessProfile
{
    public function __construct(
        private readonly HttpClient $http,
        private readonly string $root,
    ) {
    }

    public function get(): mixed
    {
        return $this->http->request('GET', $this->root . '/business-profile');
    }

    /**
     * @param array<string, mixed> $input
     */
    public function update(array $input): mixed
    {
        return $this->http->request('PUT', $this->root . '/business-profile', ['body' => $input]);
    }
}

final class AgencyPreferences
{
    public function __construct(
        private readonly HttpClient $http,
        private readonly string $root,
    ) {
    }

    public function get(): mixed
    {
        return $this->http->request('GET', $this->root . '/preferences');
    }

    /**
     * @param array<string, mixed> $input
     */
    public function update(array $input): mixed
    {
        return $this->http->request('PUT', $this->root . '/preferences', ['body' => $input]);
    }
}

final class AgencyNotificationSettings
{
    public function __construct(
        private readonly HttpClient $http,
        private readonly string $root,
    ) {
    }

    public function get(): mixed
    {
        return $this->http->request('GET', $this->root . '/notification-settings');
    }

    /**
     * @param array<string, mixed> $input
     */
    public function update(array $input): mixed
    {
        return $this->http->request('PUT', $this->root . '/notification-settings', ['body' => $input]);
    }
}

final class AgencyAccess
{
    public function __construct(
        private readonly HttpClient $http,
        private readonly string $root,
    ) {
    }

    public function get(): mixed
    {
        return $this->http->request('GET', $this->root . '/access');
    }
}

final class AgencyClientInvites
{
    public function __construct(
        private readonly HttpClient $http,
        private readonly string $root,
    ) {
    }

    public function get(string $token): mixed
    {
        return $this->http->request('GET', $this->root . '/client-invites/' . Subject::segment($token));
    }

    public function accept(string $token): mixed
    {
        return $this->http->request('POST', $this->root . '/client-invites/' . Subject::segment($token));
    }

    public function remove(string $token): mixed
    {
        return $this->http->request('DELETE', $this->root . '/client-invites/' . Subject::segment($token));
    }
}

final class AgencyPartner
{
    public readonly AgencyPartnerCustomers $customers;

    public readonly AgencyPartnerKeys $keys;

    public readonly AgencyPartnerTerms $terms;

    public readonly AgencyPartnerReport $report;

    public function __construct(
        private readonly HttpClient $http,
        private readonly string $root,
    ) {
        $this->customers = new AgencyPartnerCustomers($http, $root);
        $this->keys = new AgencyPartnerKeys($http, $root);
        $this->terms = new AgencyPartnerTerms($http, $root);
        $this->report = new AgencyPartnerReport($http, $root);
    }

    public function get(): mixed
    {
        return $this->http->request('GET', $this->root . '/partner');
    }

    /**
     * @param array<string, mixed> $input
     */
    public function request(array $input = []): mixed
    {
        return $this->http->request('POST', $this->root . '/partner/request', ['body' => $input]);
    }
}

final class AgencyPartnerCustomers
{
    public function __construct(
        private readonly HttpClient $http,
        private readonly string $root,
    ) {
    }

    public function list(): mixed
    {
        return $this->http->request('GET', $this->root . '/partner/customers');
    }

    /**
     * @param array<string, mixed> $input
     */
    public function create(array $input): mixed
    {
        return $this->http->request('POST', $this->root . '/partner/customers', ['body' => $input]);
    }

    public function end(string $id): mixed
    {
        return $this->http->request('POST', $this->root . '/partner/customers/' . Subject::segment($id) . '/end');
    }

    public function suspend(string $id): mixed
    {
        return $this->http->request('POST', $this->root . '/partner/customers/' . Subject::segment($id) . '/suspend');
    }

    public function restore(string $id): mixed
    {
        return $this->http->request('POST', $this->root . '/partner/customers/' . Subject::segment($id) . '/restore');
    }
}

final class AgencyPartnerKeys
{
    public function __construct(
        private readonly HttpClient $http,
        private readonly string $root,
    ) {
    }

    public function list(): mixed
    {
        return $this->http->request('GET', $this->root . '/partner/keys');
    }

    /**
     * @param array<string, mixed> $input
     */
    public function create(array $input): mixed
    {
        return $this->http->request('POST', $this->root . '/partner/keys', ['body' => $input]);
    }

    public function revoke(string $id): mixed
    {
        return $this->http->request('POST', $this->root . '/partner/keys/' . Subject::segment($id) . '/revoke');
    }
}

final class AgencyPartnerTerms
{
    public function __construct(
        private readonly HttpClient $http,
        private readonly string $root,
    ) {
    }

    public function get(): mixed
    {
        return $this->http->request('GET', $this->root . '/partner/terms');
    }
}

final class AgencyPartnerReport
{
    public function __construct(
        private readonly HttpClient $http,
        private readonly string $root,
    ) {
    }

    /**
     * @param array<string, mixed>|null $query
     */
    public function get(?array $query = null): mixed
    {
        return $this->http->request('GET', $this->root . '/partner/report', ['query' => $query]);
    }
}
