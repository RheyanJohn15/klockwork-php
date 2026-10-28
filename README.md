# klockwork

PHP client for the [Klockwork](https://klockwork.org) public API. Create an API key in the app, then record time, set placement bill rates, and manage invoices and payouts for agency, solo, company, or partner accounts.

The client sends the key in `X-Klockwork-Key`. Solo calls also send `X-Klockwork-Talent`. Company calls also send `X-Klockwork-Client`. Partner calls send only the key.

An agency key and a partner key are different credentials. The API rejects a partner key on agency, solo, and company routes, and it rejects an agency key on partner routes. Partnership administration for an agency key is `$kw->agency->partner`. Customer calls for a partner key are `$kw->partner`.

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
    'apiKey' => getenv('KLOCKWORK_PARTNER_API_KEY'),
]);
$customers = $partner->partner->customers->list();
$partner->partner->customers->create([
    'companyName' => 'Acme',
    'invoiceEmail' => 'ada@acme.test',
]);
```

Point the same key at a local backend:

```php
$kw = new Klockwork([
    'apiKey' => getenv('KLOCKWORK_API_KEY'),
    'environment' => 'local',
]);
```

`baseUrl` overrides `environment` when you need another host. You can also set a default `talentId` or `clientId` on the constructor and override either value on a single call.

Requests and responses are JSON arrays with the API's camelCase keys. Failed responses throw `Klockwork\KlockworkError` with `status`, `error`, and `details`.

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

Packagist reads the version from a git tag such as `v0.3.0`, not from `composer.json`. The package summary on Packagist comes from the `description` field in `composer.json`. The package name is `klockwork/klockwork`, so the Packagist account that submits it must own the `klockwork` vendor. The user agent is `klockwork-php/` plus `Klockwork\Version::VERSION`.

## Tests

```bash
composer test
```
