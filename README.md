# klockwork

PHP client for the [Klockwork](https://klockwork.org) public API. Create an API key in the app, then record time, set placement bill rates, and manage invoices and payouts for agency, solo, company, or partner accounts.

| Header | When |
| --- | --- |
| `X-Klockwork-Key` | Always (preferred). `Authorization: Bearer kw_…` is accepted by the API when this header is absent; this client always sends the dedicated header. |
| `X-Klockwork-Customer` | Partner key on agency, solo, or company: customer Clerk org id (`org_…`) or `partner_customers.id` |
| `X-Klockwork-Talent` | Solo only: talent under the customer org (partner) or the agency org (agency key) |
| `X-Klockwork-Client` | Company only: client row owned by the customer org (partner) or the agency org (agency key) |

An agency key and a partner key are different credentials. Create partner keys in the signed-in partner workspace. Create agency keys in agency settings. An agency key never calls `/api/public/partner/*`. A partner key calls relationship APIs under `$kw->partner`, and product APIs under agency, solo, or company only with `X-Klockwork-Customer` and the matching scopes. Partnership admin (create or revoke keys, terms, report) is session-only under `/api/org/partner/*` and is not exposed on this package. Identity comes from the hashed key row and the customer relationship on the API; this client does not invent JWT helpers.

Production requests go to `https://api.klockwork.org`. Local requests go to `http://localhost:3001`.

## Install

```bash
composer require klockwork/klockwork
```

Requires PHP 8.2 or newer with the `curl` and `json` extensions. The package has no runtime dependencies.

## Use

```php
use Klockwork\Klockwork;

$kw = new Klockwork([
    'apiKey' => getenv('KLOCKWORK_API_KEY'),
]);

$clients = $kw->agency->clients->list();

$solo = $kw->solo->forTalent('user_abc');
$solo->timer->session->start([
    'clientId' => '00000000-0000-4000-8000-000000000001',
]);

$company = $kw->company->forClient('00000000-0000-4000-8000-000000000002');
$invoices = $company->invoices->list();

$partner = new Klockwork([
    'apiKey' => getenv('KLOCKWORK_API_KEY'),
    'customerId' => getenv('KLOCKWORK_CUSTOMER_ORG_ID'),
]);
$me = $partner->partner->me();
$customers = $partner->partner->customers->list();
$created = $partner->partner->customers->create('Acme', 'ada@acme.test');
$partner->partner->customers->end($created->customer->id);

// Same partner key, product APIs as an accepted customer
$partner->agency->clients->list();
$as = $partner->partner->forCustomer(getenv('KLOCKWORK_CUSTOMER_ORG_ID'));
$as->solo->forTalent('user_abc')->timer->session->start([
    'clientId' => '00000000-0000-4000-8000-000000000001',
]);
$as->company->forClient('00000000-0000-4000-8000-000000000002')->invoices->list();
```

Partner software typically sets:

```env
KLOCKWORK_BASE_URL=https://api.klockwork.org
KLOCKWORK_API_KEY=kw_...
KLOCKWORK_CUSTOMER_ORG_ID=org_...
```

Copy `KLOCKWORK_CUSTOMER_ORG_ID` from the partner Customers UI after the customer accepts (or from `PartnerCustomer::$customerClerkOrgId`). Pass it as constructor `customerId` or to `$kw->partner->forCustomer(...)`.

## Partner public API

`$kw->partner` calls `/api/public/partner/*` with a partner `kw_` key. With `customerId` / `forCustomer()`, the same key calls the documented [agency](https://docs.klockwork.org/agency), [solo](https://docs.klockwork.org/solo), and [company](https://docs.klockwork.org/company) product APIs. Excluded: Slack/Teams OAuth, API key management, `/api/public/solo/public/*` share tokens, `/api/public/agency/partner/*`.

### Scopes

| Scope | Allows |
| --- | --- |
| `status:read` | `GET /api/public/partner/me` |
| `customers:read` / `customers:write` | List / invite / end customers |
| `agency:read` / `agency:write` | `/api/public/agency/*` as the customer |
| `solo:read` / `solo:write` | `/api/public/solo/*` as a talent of the customer |
| `company:read` | `/api/public/company/*` for a client of the customer |

Write implies the matching read. Missing scope returns `403` `{ "error": "Missing scope" }`.

### Relationship methods

| Method | Path | Scope |
| --- | --- | --- |
| `$kw->partner->me()` | `GET /api/public/partner/me` | `status:read` |
| `$kw->partner->customers->list()` | `GET /api/public/partner/customers` | `customers:read` |
| `$kw->partner->customers->create($companyName, $invoiceEmail)` | `POST /api/public/partner/customers` | `customers:write` |
| `$kw->partner->customers->end($id)` | `POST /api/public/partner/customers/{id}/end` | `customers:write` |

`me()` returns `PartnerMe` (`partnerId`, `status`, `subscriptionExempt`, `orgId`). A successful call always has `status: "active"`.

`customers->list()` returns a list of `PartnerCustomer` (`id`, `companyName`, `invoiceEmail`, `status`, `customerClerkOrgId`, `associatedAt`, `inviteUrl`). `customerClerkOrgId` is required for product calls after accept. `inviteUrl` is present only while the invite is pending.

`customers->create()` returns `PartnerCustomerCreated` (`customer`, `inviteUrl`, `invitationSent`). `customers->end()` returns `PartnerCustomerEnded` (`ok`).

`$kw->partner->forCustomer($id)` returns agency, solo, and company clients that send `X-Klockwork-Customer`. Solo still uses `forTalent()`; company still uses `forClient()`. Pending customers (no `customerClerkOrgId`) return `403`.

| Status | Typical `error` | When |
| --- | --- | --- |
| `400` | `X-Klockwork-Customer is required` | Product call without customer header |
| `401` | `Invalid API key` / `Unauthorized` | Missing, unknown, revoked, or expired key; wrong key type for the surface |
| `403` | `Missing scope` | Key lacks the route scope |
| `403` | `Partner is not active` | Partnership is not active |
| `403` | `This company is not an active customer of the partner` | Wrong or ended customer |
| `403` | Customer has not accepted yet | Pending invite |
| `403` | Talent/client not under the customer | Subject isolation |
| `403` | `Test keys are not accepted in this environment` | Test key against live API |
| `404` | `Partner not found` / `Customer not found` / `Not found` | Missing row, or blocked public path |
| `409` | `An invite for this email is already open` | Duplicate open invite |
| `429` | Rate limit / monthly cap | Partner keys on all surfaces |

Failed responses throw `Klockwork\KlockworkError` with `status`, `error`, and `details`. Do not log the secret or its hash.

Point the same key at a local backend:

```php
$kw = new Klockwork([
    'apiKey' => getenv('KLOCKWORK_API_KEY'),
    'environment' => 'local',
]);
```

`baseUrl` overrides `environment` when you need another host. You can also set a default `talentId`, `clientId`, or `customerId` on the constructor and override talent or client on a single call.

Agency, solo, and company requests and responses are JSON arrays with the API's camelCase keys. Partner methods return typed DTOs. Failed responses throw `Klockwork\KlockworkError` with `status`, `error`, and `details`.

## Company bill rates

Agency clients are not one shared hourly rate for every talent. A bill rate is per talent-to-client placement, with an optional client default. Time recording and agency invoices need an effective rate. The client currency is required, and placement rates and agency client invoices always use that currency. Do not fall back to the talent roster or pay rate.

Resolution order:

1. Assignment `hourlyRate`, which may be null.
2. Client default `hourlyRate`, only when `useClientDefaults` is true.
3. Otherwise the rate is missing. Company-linked time and invoice lines are rejected.

`useClientDefaults` is false on create unless you set it. Existing clients were migrated to true. When it is true, `hourlyRate`, `hoursPerDay`, `shiftStart`, `shiftEnd`, and `timezone` are required. When it is false, those fields are nullable and the API clears them. `hourlyRate`, `hoursPerDay`, `shiftStart`, `shiftEnd`, and `timezone` are `number|null` or `string|null` on responses. `currency` stays required.

```php
$kw->agency->clients->create([
    'displayName' => 'Acme',
    'contactPersonName' => 'Ada',
    'invoiceEmail' => 'ada@acme.test',
    'currency' => 'PHP',
    'useClientDefaults' => false,
]);

$kw->agency->assignments->create([
    'clientId' => $clientId,
    'talentId' => $talentId,
    'hourlyRate' => 180,
]);

$kw->agency->assignments->update($assignmentId, [
    'hourlyRate' => null,
]);
```

Assignment responses include `hourlyRate` (`number|null`) and `clientCurrency` when the client join is loaded. Update accepts `status`, `hourlyRate`, or both. At least one is required. Clearing `hourlyRate` requires an enabled client default rate.

Turning defaults off, or clearing the client default rate, while any open assignment lacks its own rate returns `400`. The message says open placements need their own rates first.

A talent can list and patch their own open placements. List items include assignment fields plus `clientUseDefaults` and `clientDefaultHourlyRate`:

```php
$placements = $kw->solo->forTalent($talentId)->placements->list();
$kw->solo->forTalent($talentId)->placements->update($placementId, [
    'hourlyRate' => 175,
]);
```

`POST /api/public/solo/timer/session/start` and `POST /api/public/solo/timer/sessions` require an effective bill rate when `orgClientId` is set or auto-resolved. `POST /api/public/agency/time` requires one when `clientId` is set. A missing rate returns `400` with a message to set a placement rate or enable a client default rate before recording time. Unlinked solo timer sessions still use solo client rates. Solo `/clients` is unchanged.

`POST /api/public/agency/invoices` prices each line from that talent's assignment rate, then the client default. Mixed talent rates on one invoice are expected. If any included hour cannot resolve a rate, the API returns `400` and names the talent and client. Existing invoice line `unitRateCents` snapshots stay immutable. Do not send a shared client rate on the invoice body.

Invoice void methods are named `voidInvoice()` because `void` is reserved in PHP.

Packagist reads the version from a git tag such as `v0.5.0`, not from `composer.json`. The package summary on Packagist comes from the `description` field in `composer.json`. The package name is `klockwork/klockwork`, so the Packagist account that submits it must own the `klockwork` vendor. The user agent is `klockwork-php/` plus `Klockwork\Version::VERSION`.

## Tests

```bash
composer test
```
