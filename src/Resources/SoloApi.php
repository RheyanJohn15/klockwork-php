<?php

declare(strict_types=1);

namespace Klockwork\Resources;

use Klockwork\HttpClient;
use Klockwork\Subject;

final class SoloApi
{
    public readonly SoloClients $clients;

    public readonly SoloPlacements $placements;

    public readonly SoloTimer $timer;

    public readonly SoloInvoices $invoices;

    public readonly SoloTimesheet $timesheet;

    public readonly SoloPayments $payments;

    public readonly SoloOverview $overview;

    public readonly SoloBilling $billing;

    public readonly SoloBusinessProfile $businessProfile;

    public readonly SoloInvoiceDefaults $invoiceDefaults;

    public readonly SoloPreferences $preferences;

    public readonly SoloNotificationSettings $notificationSettings;

    public readonly SoloPayoutSettings $payoutSettings;

    public readonly SoloIntegrations $integrations;

    public function __construct(
        private readonly HttpClient $http,
        ?string $boundTalentId = null,
    ) {
        $talentOf = static function (?string $explicit) use ($boundTalentId): string {
            return Subject::requireTalent($explicit, $boundTalentId);
        };
        $context = new SoloContext($http, $talentOf);
        $this->clients = new SoloClients($context);
        $this->placements = new SoloPlacements($context);
        $this->timer = new SoloTimer($context);
        $this->invoices = new SoloInvoices($context);
        $this->timesheet = new SoloTimesheet($context);
        $this->payments = new SoloPayments($context);
        $this->overview = new SoloOverview($context);
        $this->billing = new SoloBilling($context);
        $this->businessProfile = new SoloBusinessProfile($context);
        $this->invoiceDefaults = new SoloInvoiceDefaults($context);
        $this->preferences = new SoloPreferences($context);
        $this->notificationSettings = new SoloNotificationSettings($context);
        $this->payoutSettings = new SoloPayoutSettings($context);
        $this->integrations = new SoloIntegrations($context);
    }

    public function forTalent(string $talentId): self
    {
        return new self($this->http, $talentId);
    }
}

/**
 * @internal
 */
final class SoloContext
{
    /**
     * @param \Closure(?string): string $talentOf
     */
    public function __construct(
        public readonly HttpClient $http,
        private readonly \Closure $talentOf,
        public readonly string $root = '/api/public/solo',
    ) {
    }

    /**
     * @param array{
     *     query?: array<string, mixed>,
     *     body?: array<string, mixed>,
     *     form?: array{filename: string, contents: string, contentType: string}
     * } $extra
     */
    public function call(string $method, string $path, ?string $explicitTalentId, array $extra = []): mixed
    {
        $request = [
            'talentId' => ($this->talentOf)($explicitTalentId),
        ];
        if (array_key_exists('query', $extra)) {
            $request['query'] = $extra['query'];
        }
        if (array_key_exists('body', $extra)) {
            $request['body'] = $extra['body'];
        }
        if (array_key_exists('form', $extra)) {
            $request['form'] = $extra['form'];
        }

        return $this->http->request($method, $path, $request);
    }
}

final class SoloClients
{
    public function __construct(private readonly SoloContext $context)
    {
    }

    /**
     * @param array<string, mixed>|null $query
     */
    public function list(?array $query = null): mixed
    {
        ['talentId' => $talentId, 'rest' => $rest] = Subject::splitTalent($query);

        return $this->context->call('GET', $this->context->root . '/clients', $talentId, ['query' => $rest]);
    }

    /**
     * @param array<string, mixed> $input
     */
    public function create(array $input): mixed
    {
        ['talentId' => $talentId, 'rest' => $rest] = Subject::splitTalent($input);

        return $this->context->call('POST', $this->context->root . '/clients', $talentId, ['body' => $rest]);
    }

    /**
     * @param array<string, mixed>|null $options
     */
    public function get(string $id, ?array $options = null): mixed
    {
        return $this->context->call(
            'GET',
            $this->context->root . '/clients/' . Subject::segment($id),
            Subject::splitTalent($options)['talentId'],
        );
    }

    /**
     * @param array<string, mixed> $input
     */
    public function update(string $id, array $input): mixed
    {
        ['talentId' => $talentId, 'rest' => $rest] = Subject::splitTalent($input);

        return $this->context->call(
            'PATCH',
            $this->context->root . '/clients/' . Subject::segment($id),
            $talentId,
            ['body' => $rest],
        );
    }

    /**
     * @param array<string, mixed>|null $options
     */
    public function remove(string $id, ?array $options = null): mixed
    {
        return $this->context->call(
            'DELETE',
            $this->context->root . '/clients/' . Subject::segment($id),
            Subject::splitTalent($options)['talentId'],
        );
    }

    /**
     * @param array<string, mixed>|null $options
     */
    public function touch(string $id, ?array $options = null): mixed
    {
        return $this->context->call(
            'POST',
            $this->context->root . '/clients/' . Subject::segment($id) . '/touch',
            Subject::splitTalent($options)['talentId'],
        );
    }
}

final class SoloPlacements
{
    public function __construct(private readonly SoloContext $context)
    {
    }

    /**
     * Open company placements for the talent in `X-Klockwork-Talent`.
     *
     * Each item includes the assignment plus `clientUseDefaults` and
     * `clientDefaultHourlyRate`. `hourlyRate` is the placement override and may be null.
     *
     * @param array<string, mixed>|null $query
     */
    public function list(?array $query = null): mixed
    {
        ['talentId' => $talentId, 'rest' => $rest] = Subject::splitTalent($query);

        return $this->context->call('GET', $this->context->root . '/placements', $talentId, ['query' => $rest]);
    }

    /**
     * Set or clear this talent's own placement bill rate.
     *
     * Send `hourlyRate` as a number greater than or equal to zero, or null to clear it.
     * Clearing requires an enabled client default rate; otherwise the API returns 400.
     *
     * @param array{hourlyRate: float|int|null, talentId?: string} $input
     */
    public function update(string $id, array $input): mixed
    {
        ['talentId' => $talentId, 'rest' => $rest] = Subject::splitTalent($input);

        return $this->context->call(
            'PATCH',
            $this->context->root . '/placements/' . Subject::segment($id),
            $talentId,
            ['body' => $rest],
        );
    }
}

final class SoloTimer
{
    public readonly SoloTimerSession $session;

    public readonly SoloTimerSessions $sessions;

    public function __construct(SoloContext $context)
    {
        $this->session = new SoloTimerSession($context);
        $this->sessions = new SoloTimerSessions($context);
    }
}

final class SoloTimerSession
{
    public readonly SoloTimerBreak $break;

    public function __construct(private readonly SoloContext $context)
    {
        $this->break = new SoloTimerBreak($context);
    }

    /**
     * @param array<string, mixed>|null $options
     */
    public function get(?array $options = null): mixed
    {
        return $this->context->call(
            'GET',
            $this->context->root . '/timer/session',
            Subject::splitTalent($options)['talentId'],
        );
    }

    /**
     * @param array<string, mixed> $input
     */
    public function update(array $input): mixed
    {
        ['talentId' => $talentId, 'rest' => $rest] = Subject::splitTalent($input);

        return $this->context->call('PATCH', $this->context->root . '/timer/session', $talentId, ['body' => $rest]);
    }

    /**
     * When `orgClientId` is set, or the API auto-resolves a company client, an effective bill
     * rate is required. Missing rate returns 400. Sessions with no org client still use solo client rates.
     *
     * @param array<string, mixed> $input
     */
    public function start(array $input): mixed
    {
        ['talentId' => $talentId, 'rest' => $rest] = Subject::splitTalent($input);

        return $this->context->call('POST', $this->context->root . '/timer/session/start', $talentId, ['body' => $rest]);
    }

    /**
     * @param array<string, mixed> $input
     */
    public function save(array $input): mixed
    {
        ['talentId' => $talentId, 'rest' => $rest] = Subject::splitTalent($input);

        return $this->context->call('POST', $this->context->root . '/timer/session/save', $talentId, ['body' => $rest]);
    }

    /**
     * @param array<string, mixed>|null $options
     */
    public function discard(?array $options = null): mixed
    {
        return $this->context->call(
            'POST',
            $this->context->root . '/timer/session/discard',
            Subject::splitTalent($options)['talentId'],
        );
    }

    /**
     * @param array<string, mixed>|null $options
     */
    public function resume(?array $options = null): mixed
    {
        return $this->context->call(
            'POST',
            $this->context->root . '/timer/session/resume',
            Subject::splitTalent($options)['talentId'],
        );
    }

    /**
     * @param array<string, mixed>|null $options
     */
    public function timeout(?array $options = null): mixed
    {
        return $this->context->call(
            'POST',
            $this->context->root . '/timer/session/timeout',
            Subject::splitTalent($options)['talentId'],
        );
    }
}

final class SoloTimerBreak
{
    public function __construct(private readonly SoloContext $context)
    {
    }

    /**
     * @param array<string, mixed> $input
     */
    public function start(array $input): mixed
    {
        ['talentId' => $talentId, 'rest' => $rest] = Subject::splitTalent($input);

        return $this->context->call(
            'POST',
            $this->context->root . '/timer/session/break/start',
            $talentId,
            ['body' => $rest],
        );
    }

    /**
     * @param array<string, mixed>|null $options
     */
    public function end(?array $options = null): mixed
    {
        return $this->context->call(
            'POST',
            $this->context->root . '/timer/session/break/end',
            Subject::splitTalent($options)['talentId'],
        );
    }
}

final class SoloTimerSessions
{
    public function __construct(private readonly SoloContext $context)
    {
    }

    /**
     * @param array<string, mixed> $query
     */
    public function list(array $query): mixed
    {
        ['talentId' => $talentId, 'rest' => $rest] = Subject::splitTalent($query);

        return $this->context->call('GET', $this->context->root . '/timer/sessions', $talentId, ['query' => $rest]);
    }

    /**
     * Attributing the session to an org client requires an effective bill rate.
     * Missing rate returns 400. Unlinked sessions still use solo client rates.
     *
     * @param array<string, mixed> $input
     */
    public function create(array $input): mixed
    {
        ['talentId' => $talentId, 'rest' => $rest] = Subject::splitTalent($input);

        return $this->context->call('POST', $this->context->root . '/timer/sessions', $talentId, ['body' => $rest]);
    }

    /**
     * @param array<string, mixed>|null $options
     */
    public function get(string $id, ?array $options = null): mixed
    {
        return $this->context->call(
            'GET',
            $this->context->root . '/timer/sessions/' . Subject::segment($id),
            Subject::splitTalent($options)['talentId'],
        );
    }

    /**
     * @param array<string, mixed> $input
     */
    public function update(string $id, array $input): mixed
    {
        ['talentId' => $talentId, 'rest' => $rest] = Subject::splitTalent($input);

        return $this->context->call(
            'PATCH',
            $this->context->root . '/timer/sessions/' . Subject::segment($id),
            $talentId,
            ['body' => $rest],
        );
    }

    /**
     * @param array<string, mixed> $input
     */
    public function remove(string $id, array $input): mixed
    {
        ['talentId' => $talentId, 'rest' => $rest] = Subject::splitTalent($input);

        return $this->context->call(
            'DELETE',
            $this->context->root . '/timer/sessions/' . Subject::segment($id),
            $talentId,
            ['body' => $rest],
        );
    }

    /**
     * @param array<string, mixed>|null $options
     */
    public function events(string $id, ?array $options = null): mixed
    {
        return $this->context->call(
            'GET',
            $this->context->root . '/timer/sessions/' . Subject::segment($id) . '/events',
            Subject::splitTalent($options)['talentId'],
        );
    }
}

final class SoloInvoices
{
    public function __construct(private readonly SoloContext $context)
    {
    }

    /**
     * @param array<string, mixed>|null $options
     */
    public function list(?array $options = null): mixed
    {
        return $this->context->call(
            'GET',
            $this->context->root . '/invoices',
            Subject::splitTalent($options)['talentId'],
        );
    }

    /**
     * @param array<string, mixed> $input
     */
    public function create(array $input): mixed
    {
        ['talentId' => $talentId, 'rest' => $rest] = Subject::splitTalent($input);

        return $this->context->call('POST', $this->context->root . '/invoices', $talentId, ['body' => $rest]);
    }

    /**
     * @param array<string, mixed>|null $options
     */
    public function get(string $id, ?array $options = null): mixed
    {
        return $this->context->call(
            'GET',
            $this->context->root . '/invoices/' . Subject::segment($id),
            Subject::splitTalent($options)['talentId'],
        );
    }

    /**
     * @param array<string, mixed> $input
     */
    public function update(string $id, array $input): mixed
    {
        ['talentId' => $talentId, 'rest' => $rest] = Subject::splitTalent($input);

        return $this->context->call(
            'PATCH',
            $this->context->root . '/invoices/' . Subject::segment($id),
            $talentId,
            ['body' => $rest],
        );
    }

    /**
     * @param array<string, mixed>|null $input
     */
    public function voidInvoice(string $id, ?array $input = null): mixed
    {
        ['talentId' => $talentId, 'rest' => $rest] = Subject::splitTalent($input);

        return $this->context->call(
            'POST',
            $this->context->root . '/invoices/' . Subject::segment($id) . '/void',
            $talentId,
            ['body' => $rest],
        );
    }

    /**
     * @param array<string, mixed>|null $options
     */
    public function events(string $id, ?array $options = null): mixed
    {
        return $this->context->call(
            'GET',
            $this->context->root . '/invoices/' . Subject::segment($id) . '/events',
            Subject::splitTalent($options)['talentId'],
        );
    }

    /**
     * @param array<string, mixed>|null $options
     */
    public function shares(string $id, ?array $options = null): mixed
    {
        return $this->context->call(
            'GET',
            $this->context->root . '/invoices/' . Subject::segment($id) . '/shares',
            Subject::splitTalent($options)['talentId'],
        );
    }

    /**
     * @param array<string, mixed>|null $input
     */
    public function createShare(string $id, ?array $input = null): mixed
    {
        ['talentId' => $talentId, 'rest' => $rest] = Subject::splitTalent($input);

        return $this->context->call(
            'POST',
            $this->context->root . '/invoices/' . Subject::segment($id) . '/shares',
            $talentId,
            ['body' => $rest],
        );
    }

    /**
     * @param array<string, mixed>|null $options
     */
    public function revokeShare(string $id, ?array $options = null): mixed
    {
        return $this->context->call(
            'POST',
            $this->context->root . '/invoices/shares/' . Subject::segment($id) . '/revoke',
            Subject::splitTalent($options)['talentId'],
        );
    }

    /**
     * @param array<string, mixed>|null $options
     */
    public function unbilled(?array $options = null): mixed
    {
        return $this->context->call(
            'GET',
            $this->context->root . '/invoices/unbilled',
            Subject::splitTalent($options)['talentId'],
        );
    }
}

final class SoloTimesheet
{
    public readonly SoloTimesheetShares $shares;

    public function __construct(SoloContext $context)
    {
        $this->shares = new SoloTimesheetShares($context);
    }
}

final class SoloTimesheetShares
{
    public function __construct(private readonly SoloContext $context)
    {
    }

    /**
     * @param array<string, mixed>|null $query
     */
    public function list(?array $query = null): mixed
    {
        ['talentId' => $talentId, 'rest' => $rest] = Subject::splitTalent($query);

        return $this->context->call('GET', $this->context->root . '/timesheet/shares', $talentId, ['query' => $rest]);
    }

    /**
     * @param array<string, mixed> $input
     */
    public function create(array $input): mixed
    {
        ['talentId' => $talentId, 'rest' => $rest] = Subject::splitTalent($input);

        return $this->context->call('POST', $this->context->root . '/timesheet/shares', $talentId, ['body' => $rest]);
    }

    /**
     * @param array<string, mixed>|null $options
     */
    public function revoke(string $id, ?array $options = null): mixed
    {
        return $this->context->call(
            'POST',
            $this->context->root . '/timesheet/shares/' . Subject::segment($id) . '/revoke',
            Subject::splitTalent($options)['talentId'],
        );
    }
}

final class SoloPayments
{
    public function __construct(private readonly SoloContext $context)
    {
    }

    /**
     * @param array<string, mixed>|null $options
     */
    public function list(?array $options = null): mixed
    {
        return $this->context->call(
            'GET',
            $this->context->root . '/payments',
            Subject::splitTalent($options)['talentId'],
        );
    }

    /**
     * @param array<string, mixed> $input
     */
    public function create(array $input): mixed
    {
        ['talentId' => $talentId, 'rest' => $rest] = Subject::splitTalent($input);

        return $this->context->call('POST', $this->context->root . '/payments', $talentId, ['body' => $rest]);
    }

    /**
     * @param array<string, mixed>|null $options
     */
    public function get(string $id, ?array $options = null): mixed
    {
        return $this->context->call(
            'GET',
            $this->context->root . '/payments/' . Subject::segment($id),
            Subject::splitTalent($options)['talentId'],
        );
    }

    /**
     * @param array<string, mixed> $options
     */
    public function uploadAttachment(string $paymentId, string $contents, array $options = []): mixed
    {
        $talentId = $options['talentId'] ?? null;
        if ($talentId !== null && !is_string($talentId)) {
            throw new \InvalidArgumentException('talentId must be a string.');
        }
        $filename = $options['filename'] ?? 'attachment';
        $contentType = $options['contentType'] ?? 'application/octet-stream';
        if (!is_string($filename) || !is_string($contentType)) {
            throw new \InvalidArgumentException('filename and contentType must be strings.');
        }

        return $this->context->call(
            'POST',
            $this->context->root . '/payments/' . Subject::segment($paymentId) . '/attachments',
            $talentId,
            [
                'form' => [
                    'filename' => $filename,
                    'contents' => $contents,
                    'contentType' => $contentType,
                ],
            ],
        );
    }

    /**
     * @param array<string, mixed>|null $options
     */
    public function deleteAttachment(string $paymentId, string $attachmentId, ?array $options = null): mixed
    {
        return $this->context->call(
            'DELETE',
            $this->context->root . '/payments/' . Subject::segment($paymentId) . '/attachments/' . Subject::segment($attachmentId),
            Subject::splitTalent($options)['talentId'],
        );
    }

    /**
     * @param array<string, mixed>|null $options
     */
    public function attachmentUrl(string $paymentId, string $attachmentId, ?array $options = null): mixed
    {
        return $this->context->call(
            'GET',
            $this->context->root . '/payments/' . Subject::segment($paymentId) . '/attachments/' . Subject::segment($attachmentId) . '/url',
            Subject::splitTalent($options)['talentId'],
        );
    }
}

final class SoloOverview
{
    public function __construct(private readonly SoloContext $context)
    {
    }

    /**
     * @param array<string, mixed>|null $options
     */
    public function get(?array $options = null): mixed
    {
        return $this->context->call(
            'GET',
            $this->context->root . '/overview',
            Subject::splitTalent($options)['talentId'],
        );
    }
}

final class SoloBilling
{
    public function __construct(private readonly SoloContext $context)
    {
    }

    /**
     * @param array<string, mixed>|null $options
     */
    public function get(?array $options = null): mixed
    {
        return $this->context->call(
            'GET',
            $this->context->root . '/billing',
            Subject::splitTalent($options)['talentId'],
        );
    }

    /**
     * @param array<string, mixed>|null $options
     */
    public function sync(?array $options = null): mixed
    {
        return $this->context->call(
            'POST',
            $this->context->root . '/billing',
            Subject::splitTalent($options)['talentId'],
        );
    }
}

final class SoloBusinessProfile
{
    public function __construct(private readonly SoloContext $context)
    {
    }

    /**
     * @param array<string, mixed>|null $options
     */
    public function get(?array $options = null): mixed
    {
        return $this->context->call(
            'GET',
            $this->context->root . '/business-profile',
            Subject::splitTalent($options)['talentId'],
        );
    }

    /**
     * @param array<string, mixed> $input
     */
    public function update(array $input): mixed
    {
        ['talentId' => $talentId, 'rest' => $rest] = Subject::splitTalent($input);

        return $this->context->call('PUT', $this->context->root . '/business-profile', $talentId, ['body' => $rest]);
    }
}

final class SoloInvoiceDefaults
{
    public function __construct(private readonly SoloContext $context)
    {
    }

    /**
     * @param array<string, mixed>|null $options
     */
    public function get(?array $options = null): mixed
    {
        return $this->context->call(
            'GET',
            $this->context->root . '/invoice-defaults',
            Subject::splitTalent($options)['talentId'],
        );
    }

    /**
     * @param array<string, mixed> $input
     */
    public function update(array $input): mixed
    {
        ['talentId' => $talentId, 'rest' => $rest] = Subject::splitTalent($input);

        return $this->context->call('PUT', $this->context->root . '/invoice-defaults', $talentId, ['body' => $rest]);
    }
}

final class SoloPreferences
{
    public function __construct(private readonly SoloContext $context)
    {
    }

    /**
     * @param array<string, mixed>|null $query
     */
    public function get(?array $query = null): mixed
    {
        ['talentId' => $talentId, 'rest' => $rest] = Subject::splitTalent($query);

        return $this->context->call('GET', $this->context->root . '/preferences', $talentId, ['query' => $rest]);
    }

    /**
     * @param array<string, mixed> $input
     */
    public function update(array $input): mixed
    {
        ['talentId' => $talentId, 'rest' => $rest] = Subject::splitTalent($input);

        return $this->context->call('PUT', $this->context->root . '/preferences', $talentId, ['body' => $rest]);
    }
}

final class SoloNotificationSettings
{
    public function __construct(private readonly SoloContext $context)
    {
    }

    /**
     * @param array<string, mixed>|null $options
     */
    public function get(?array $options = null): mixed
    {
        return $this->context->call(
            'GET',
            $this->context->root . '/notification-settings',
            Subject::splitTalent($options)['talentId'],
        );
    }

    /**
     * @param array<string, mixed> $input
     */
    public function update(array $input): mixed
    {
        ['talentId' => $talentId, 'rest' => $rest] = Subject::splitTalent($input);

        return $this->context->call('PUT', $this->context->root . '/notification-settings', $talentId, ['body' => $rest]);
    }
}

final class SoloPayoutSettings
{
    public function __construct(private readonly SoloContext $context)
    {
    }

    /**
     * @param array<string, mixed>|null $options
     */
    public function get(?array $options = null): mixed
    {
        return $this->context->call(
            'GET',
            $this->context->root . '/payout-settings',
            Subject::splitTalent($options)['talentId'],
        );
    }

    /**
     * @param array<string, mixed> $input
     */
    public function update(array $input): mixed
    {
        ['talentId' => $talentId, 'rest' => $rest] = Subject::splitTalent($input);

        return $this->context->call('PUT', $this->context->root . '/payout-settings', $talentId, ['body' => $rest]);
    }
}

final class SoloIntegrations
{
    public readonly SoloSlack $slack;

    public readonly SoloTeams $teams;

    public function __construct(SoloContext $context)
    {
        $this->slack = new SoloSlack($context);
        $this->teams = new SoloTeams($context);
    }
}

final class SoloSlack
{
    public function __construct(private readonly SoloContext $context)
    {
    }

    /**
     * @param array<string, mixed>|null $options
     */
    public function connections(?array $options = null): mixed
    {
        return $this->context->call(
            'GET',
            $this->context->root . '/integrations/slack',
            Subject::splitTalent($options)['talentId'],
        );
    }

    /**
     * @param array<string, mixed> $query
     */
    public function channels(array $query): mixed
    {
        ['talentId' => $talentId, 'rest' => $rest] = Subject::splitTalent($query);

        return $this->context->call(
            'GET',
            $this->context->root . '/integrations/slack/channels',
            $talentId,
            ['query' => $rest],
        );
    }

    /**
     * @param array<string, mixed> $input
     */
    public function createBinding(array $input): mixed
    {
        ['talentId' => $talentId, 'rest' => $rest] = Subject::splitTalent($input);

        return $this->context->call('POST', $this->context->root . '/integrations/slack', $talentId, ['body' => $rest]);
    }

    /**
     * @param array<string, mixed> $input
     */
    public function updateBinding(string $id, array $input): mixed
    {
        ['talentId' => $talentId, 'rest' => $rest] = Subject::splitTalent($input);

        return $this->context->call(
            'PATCH',
            $this->context->root . '/integrations/slack/bindings/' . Subject::segment($id),
            $talentId,
            ['body' => $rest],
        );
    }

    /**
     * @param array<string, mixed>|null $options
     */
    public function removeBinding(string $id, ?array $options = null): mixed
    {
        return $this->context->call(
            'DELETE',
            $this->context->root . '/integrations/slack/bindings/' . Subject::segment($id),
            Subject::splitTalent($options)['talentId'],
        );
    }

    /**
     * @param array<string, mixed>|null $options
     */
    public function disconnectInstall(string $id, ?array $options = null): mixed
    {
        return $this->context->call(
            'DELETE',
            $this->context->root . '/integrations/slack/installs/' . Subject::segment($id),
            Subject::splitTalent($options)['talentId'],
        );
    }

    /**
     * @param array<string, mixed>|null $options
     */
    public function startTest(string $id, ?array $options = null): mixed
    {
        return $this->context->call(
            'POST',
            $this->context->root . '/integrations/slack/bindings/' . Subject::segment($id) . '/test',
            Subject::splitTalent($options)['talentId'],
        );
    }

    /**
     * @param array<string, mixed>|null $options
     */
    public function getTest(string $bindingId, string $testId, ?array $options = null): mixed
    {
        return $this->context->call(
            'GET',
            $this->context->root . '/integrations/slack/bindings/' . Subject::segment($bindingId) . '/test/' . Subject::segment($testId),
            Subject::splitTalent($options)['talentId'],
        );
    }
}

final class SoloTeams
{
    public function __construct(private readonly SoloContext $context)
    {
    }

    /**
     * @param array<string, mixed>|null $options
     */
    public function connections(?array $options = null): mixed
    {
        return $this->context->call(
            'GET',
            $this->context->root . '/integrations/teams',
            Subject::splitTalent($options)['talentId'],
        );
    }

    /**
     * @param array<string, mixed> $query
     */
    public function channels(array $query): mixed
    {
        ['talentId' => $talentId, 'rest' => $rest] = Subject::splitTalent($query);

        return $this->context->call(
            'GET',
            $this->context->root . '/integrations/teams/channels',
            $talentId,
            ['query' => $rest],
        );
    }

    /**
     * @param array<string, mixed> $input
     */
    public function createBinding(array $input): mixed
    {
        ['talentId' => $talentId, 'rest' => $rest] = Subject::splitTalent($input);

        return $this->context->call('POST', $this->context->root . '/integrations/teams', $talentId, ['body' => $rest]);
    }

    /**
     * @param array<string, mixed> $input
     */
    public function updateBinding(string $id, array $input): mixed
    {
        ['talentId' => $talentId, 'rest' => $rest] = Subject::splitTalent($input);

        return $this->context->call(
            'PATCH',
            $this->context->root . '/integrations/teams/bindings/' . Subject::segment($id),
            $talentId,
            ['body' => $rest],
        );
    }

    /**
     * @param array<string, mixed>|null $options
     */
    public function removeBinding(string $id, ?array $options = null): mixed
    {
        return $this->context->call(
            'DELETE',
            $this->context->root . '/integrations/teams/bindings/' . Subject::segment($id),
            Subject::splitTalent($options)['talentId'],
        );
    }

    /**
     * @param array<string, mixed>|null $options
     */
    public function disconnectInstall(string $id, ?array $options = null): mixed
    {
        return $this->context->call(
            'DELETE',
            $this->context->root . '/integrations/teams/installs/' . Subject::segment($id),
            Subject::splitTalent($options)['talentId'],
        );
    }
}
