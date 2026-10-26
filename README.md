# klockwork

Client for the [Klockwork](https://klockwork.org) public API. Create an API key in the app, then call agency, solo, company, or partner routes from another service.

The client sends the key in `X-Klockwork-Key`. Solo calls also send `X-Klockwork-Talent`. Company calls also send `X-Klockwork-Client`. Partner calls send only the key.

An agency key and a partner key are different credentials. The API rejects a partner key on agency, solo, and company routes, and it rejects an agency key on partner routes. Partnership administration for an agency key is `$kw->agency->partner`. Customer calls for a partner key are `$kw->partner`.

Production requests go to `https://klockwork.org`. Local requests go to `http://localhost:3001`.

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

Invoice void methods are named `voidInvoice()` because `void` is reserved in PHP.

Packagist reads the version from a git tag such as `v0.1.0`, not from `composer.json`. The package name is `klockwork/klockwork`, so the Packagist account that submits it must own the `klockwork` vendor.

## Tests

```bash
composer test
```
