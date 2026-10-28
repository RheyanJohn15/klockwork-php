# Changelog

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
