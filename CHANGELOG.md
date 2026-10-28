# Changelog

## 0.5.0

Partner customer impersonation / product parity.

- Constructor `customerId` and `$kw->partner->forCustomer($id)` send `X-Klockwork-Customer` on agency, solo, and company calls with the same partner key.
- Relationship calls (`me`, customers list/create/end) stay unbound and do not send the customer header.
- Documented env vars `KLOCKWORK_API_KEY` and `KLOCKWORK_CUSTOMER_ORG_ID`, product scopes (`agency:*`, `solo:*`, `company:read`), and errors for missing customer header, pending customer, and wrong customer.

## 0.4.0

Partner public API (`/api/public/partner`).

- `$kw->partner->me()` returns `PartnerMe`.
- `$kw->partner->customers->list()` returns a list of `PartnerCustomer`.
- `$kw->partner->customers->create($companyName, $invoiceEmail)` returns `PartnerCustomerCreated`.
- `$kw->partner->customers->end($id)` returns `PartnerCustomerEnded`.
- Removed `$kw->agency->partner`. Partnership admin is session-only under `/api/org/partner/*` and those former public agency partner paths return `404`.
- Partner and agency keys stay separate. An agency key on partner returns `401`. A partner key needs `X-Klockwork-Customer` plus product scopes for agency, solo, and company (see 0.5.0).
- Documented partner scopes (`status:read`, `customers:read`, `customers:write`) and common `401` / `403` / `409` / `429` errors.

## 0.3.0

Production requests use `https://api.klockwork.org`. Earlier releases sent them to `https://klockwork.org`.

## 0.2.0

Company clients no longer have one required hourly rate for every talent. Bill rates are per placement, with an optional client default. This is a breaking change for callers that treated agency client `hourlyRate` as a required number.

- Agency client `hourlyRate`, `hoursPerDay`, `shiftStart`, `shiftEnd`, and `timezone` are nullable. `useClientDefaults` is new and defaults to `false` on create. `currency` stays required.
- Rate fields are required only when `useClientDefaults` is `true`. Turning defaults off clears them. That update returns `400` when an open assignment has no rate of its own.
- Assignment create accepts optional `hourlyRate`. Assignment update accepts `status` and/or `hourlyRate`. Responses include `hourlyRate` and, when the client is loaded, `clientCurrency`.
- Solo talents can list and update their own placements at `/api/public/solo/placements` via `$kw->solo->placements`.
- Company-linked timer starts, manual sessions, and agency time entries return `400` when no effective bill rate resolves. The message asks you to set a placement rate or enable a client default rate before recording time.
- Agency invoices price each line from the assignment rate, then the client default. A missing rate returns `400` naming the talent and client. Line `unitRateCents` snapshots stay immutable.
- Effective rate order: assignment `hourlyRate`, then the client default only when `useClientDefaults` is true. There is no fallback to the talent pay rate. Currency is always the client currency.
- Solo `/clients` is unchanged.

## 0.1.0

Initial public client for agency, solo, company, and partner routes.
