<?php

declare(strict_types=1);

namespace Klockwork\Tests;

use Klockwork\Klockwork;

final class RouteCatalog
{
    public const ID = '00000000-0000-4000-8000-000000000001';

    /**
     * @return array<string, array{0: string, 1: string, 2: \Closure(Klockwork): mixed, 3: ?string}>
     */
    public static function cases(): array
    {
        $id = self::ID;
        $routes = [];
        $add = static function (string $name, string $method, string $path, \Closure $call, ?string $body = null) use (&$routes): void {
            $routes[$name] = [$method, $path, $call, $body];
        };
        $json = ['name' => 'x'];

        $add('agency clients list', 'GET', '/api/public/agency/clients', static fn (Klockwork $kw) => $kw->agency->clients->list());
        $add('agency clients create', 'POST', '/api/public/agency/clients', static fn (Klockwork $kw) => $kw->agency->clients->create($json));
        $add('agency clients get', 'GET', "/api/public/agency/clients/{$id}", static fn (Klockwork $kw) => $kw->agency->clients->get($id));
        $add('agency clients update', 'PATCH', "/api/public/agency/clients/{$id}", static fn (Klockwork $kw) => $kw->agency->clients->update($id, $json));
        $add('agency clients remove', 'DELETE', "/api/public/agency/clients/{$id}", static fn (Klockwork $kw) => $kw->agency->clients->remove($id));
        $add('agency clients invite', 'POST', "/api/public/agency/clients/{$id}/invite", static fn (Klockwork $kw) => $kw->agency->clients->invite($id), 'none');
        $add('agency clients revoke invite', 'POST', "/api/public/agency/clients/{$id}/invite/revoke", static fn (Klockwork $kw) => $kw->agency->clients->revokeInvite($id), 'none');
        $add('agency clients cover', 'POST', "/api/public/agency/clients/{$id}/cover", static fn (Klockwork $kw) => $kw->agency->clients->cover($id), 'none');
        $add('agency talents list', 'GET', '/api/public/agency/talents', static fn (Klockwork $kw) => $kw->agency->talents->list());
        $add('agency talents create', 'POST', '/api/public/agency/talents', static fn (Klockwork $kw) => $kw->agency->talents->create($json));
        $add('agency talents get', 'GET', "/api/public/agency/talents/{$id}", static fn (Klockwork $kw) => $kw->agency->talents->get($id));
        $add('agency talents update', 'PATCH', "/api/public/agency/talents/{$id}", static fn (Klockwork $kw) => $kw->agency->talents->update($id, $json));
        $add('agency talents remove', 'DELETE', "/api/public/agency/talents/{$id}", static fn (Klockwork $kw) => $kw->agency->talents->remove($id));
        $add('agency talents invite', 'POST', "/api/public/agency/talents/{$id}/invite", static fn (Klockwork $kw) => $kw->agency->talents->invite($id), 'none');
        $add('agency assignments list', 'GET', '/api/public/agency/assignments', static fn (Klockwork $kw) => $kw->agency->assignments->list());
        $add('agency assignments create', 'POST', '/api/public/agency/assignments', static fn (Klockwork $kw) => $kw->agency->assignments->create($json));
        $add('agency assignments get', 'GET', "/api/public/agency/assignments/{$id}", static fn (Klockwork $kw) => $kw->agency->assignments->get($id));
        $add('agency assignments update', 'PATCH', "/api/public/agency/assignments/{$id}", static fn (Klockwork $kw) => $kw->agency->assignments->update($id, ['status' => 'ended']));
        $add('agency assignments update rate', 'PATCH', "/api/public/agency/assignments/{$id}", static fn (Klockwork $kw) => $kw->agency->assignments->update($id, [
            'status' => 'active',
            'hourlyRate' => 180,
        ]), '{"status":"active","hourlyRate":180}');
        $add('agency time list', 'GET', '/api/public/agency/time?from=a&to=b', static fn (Klockwork $kw) => $kw->agency->time->list(['from' => 'a', 'to' => 'b']));
        $add('agency time create', 'POST', '/api/public/agency/time', static fn (Klockwork $kw) => $kw->agency->time->create($json));
        $add('agency invoices list', 'GET', '/api/public/agency/invoices', static fn (Klockwork $kw) => $kw->agency->invoices->list());
        $add('agency invoices create', 'POST', '/api/public/agency/invoices', static fn (Klockwork $kw) => $kw->agency->invoices->create($json));
        $add('agency invoices get', 'GET', "/api/public/agency/invoices/{$id}", static fn (Klockwork $kw) => $kw->agency->invoices->get($id));
        $add('agency invoices void', 'POST', "/api/public/agency/invoices/{$id}/void", static fn (Klockwork $kw) => $kw->agency->invoices->voidInvoice($id), 'none');
        $add('agency payments list', 'GET', '/api/public/agency/payments', static fn (Klockwork $kw) => $kw->agency->payments->list());
        $add('agency payments create', 'POST', '/api/public/agency/payments', static fn (Klockwork $kw) => $kw->agency->payments->create(['invoiceIds' => [$id], 'method' => 'bank']));
        $add('agency business profile get', 'GET', '/api/public/agency/business-profile', static fn (Klockwork $kw) => $kw->agency->businessProfile->get());
        $add('agency business profile update', 'PUT', '/api/public/agency/business-profile', static fn (Klockwork $kw) => $kw->agency->businessProfile->update($json));
        $add('agency preferences get', 'GET', '/api/public/agency/preferences', static fn (Klockwork $kw) => $kw->agency->preferences->get());
        $add('agency preferences update', 'PUT', '/api/public/agency/preferences', static fn (Klockwork $kw) => $kw->agency->preferences->update($json));
        $add('agency notification settings get', 'GET', '/api/public/agency/notification-settings', static fn (Klockwork $kw) => $kw->agency->notificationSettings->get());
        $add('agency notification settings update', 'PUT', '/api/public/agency/notification-settings', static fn (Klockwork $kw) => $kw->agency->notificationSettings->update([
            'emailInvoicePaid' => true,
            'emailInviteAccepted' => true,
            'emailPayoutSent' => false,
        ]));
        $add('agency access', 'GET', '/api/public/agency/access', static fn (Klockwork $kw) => $kw->agency->access->get());
        $add('agency client invites get', 'GET', "/api/public/agency/client-invites/{$id}", static fn (Klockwork $kw) => $kw->agency->clientInvites->get($id));
        $add('agency client invites accept', 'POST', "/api/public/agency/client-invites/{$id}", static fn (Klockwork $kw) => $kw->agency->clientInvites->accept($id), 'none');
        $add('agency client invites remove', 'DELETE', "/api/public/agency/client-invites/{$id}", static fn (Klockwork $kw) => $kw->agency->clientInvites->remove($id), 'none');
        $add('agency partner get', 'GET', '/api/public/agency/partner', static fn (Klockwork $kw) => $kw->agency->partner->get());
        $add('agency partner request', 'POST', '/api/public/agency/partner/request', static fn (Klockwork $kw) => $kw->agency->partner->request(), 'empty');
        $add('agency partner customers list', 'GET', '/api/public/agency/partner/customers', static fn (Klockwork $kw) => $kw->agency->partner->customers->list());
        $add('agency partner customers create', 'POST', '/api/public/agency/partner/customers', static fn (Klockwork $kw) => $kw->agency->partner->customers->create([
            'companyName' => 'Acme',
            'invoiceEmail' => 'ada@acme.test',
        ]));
        $add('agency partner customers end', 'POST', "/api/public/agency/partner/customers/{$id}/end", static fn (Klockwork $kw) => $kw->agency->partner->customers->end($id), 'none');
        $add('agency partner customers suspend', 'POST', "/api/public/agency/partner/customers/{$id}/suspend", static fn (Klockwork $kw) => $kw->agency->partner->customers->suspend($id), 'none');
        $add('agency partner customers restore', 'POST', "/api/public/agency/partner/customers/{$id}/restore", static fn (Klockwork $kw) => $kw->agency->partner->customers->restore($id), 'none');
        $add('agency partner keys list', 'GET', '/api/public/agency/partner/keys', static fn (Klockwork $kw) => $kw->agency->partner->keys->list());
        $add('agency partner keys create', 'POST', '/api/public/agency/partner/keys', static fn (Klockwork $kw) => $kw->agency->partner->keys->create([
            'name' => 'live',
            'environment' => 'live',
            'scopes' => ['customers:read'],
        ]));
        $add('agency partner keys revoke', 'POST', "/api/public/agency/partner/keys/{$id}/revoke", static fn (Klockwork $kw) => $kw->agency->partner->keys->revoke($id), 'none');
        $add('agency partner terms', 'GET', '/api/public/agency/partner/terms', static fn (Klockwork $kw) => $kw->agency->partner->terms->get());
        $add('agency partner report', 'GET', '/api/public/agency/partner/report?periodStart=2026-01-01', static fn (Klockwork $kw) => $kw->agency->partner->report->get(['periodStart' => '2026-01-01']));

        $add('partner me', 'GET', '/api/public/partner/me', static fn (Klockwork $kw) => $kw->partner->me());
        $add('partner customers list', 'GET', '/api/public/partner/customers', static fn (Klockwork $kw) => $kw->partner->customers->list());
        $add('partner customers create', 'POST', '/api/public/partner/customers', static fn (Klockwork $kw) => $kw->partner->customers->create([
            'companyName' => 'Acme',
            'invoiceEmail' => 'ada@acme.test',
        ]));
        $add('partner customers end', 'POST', "/api/public/partner/customers/{$id}/end", static fn (Klockwork $kw) => $kw->partner->customers->end($id), 'none');

        $add('solo clients list', 'GET', '/api/public/solo/clients', static fn (Klockwork $kw) => $kw->solo->clients->list());
        $add('solo clients create', 'POST', '/api/public/solo/clients', static fn (Klockwork $kw) => $kw->solo->clients->create($json));
        $add('solo clients get', 'GET', "/api/public/solo/clients/{$id}", static fn (Klockwork $kw) => $kw->solo->clients->get($id));
        $add('solo clients update', 'PATCH', "/api/public/solo/clients/{$id}", static fn (Klockwork $kw) => $kw->solo->clients->update($id, $json));
        $add('solo clients remove', 'DELETE', "/api/public/solo/clients/{$id}", static fn (Klockwork $kw) => $kw->solo->clients->remove($id));
        $add('solo clients touch', 'POST', "/api/public/solo/clients/{$id}/touch", static fn (Klockwork $kw) => $kw->solo->clients->touch($id), 'none');
        $add('solo placements list', 'GET', '/api/public/solo/placements', static fn (Klockwork $kw) => $kw->solo->placements->list());
        $add('solo placements update', 'PATCH', "/api/public/solo/placements/{$id}", static fn (Klockwork $kw) => $kw->solo->placements->update($id, ['hourlyRate' => null]), '{"hourlyRate":null}');
        $add('solo timer session get', 'GET', '/api/public/solo/timer/session', static fn (Klockwork $kw) => $kw->solo->timer->session->get());
        $add('solo timer session update', 'PATCH', '/api/public/solo/timer/session', static fn (Klockwork $kw) => $kw->solo->timer->session->update($json));
        $add('solo timer session start', 'POST', '/api/public/solo/timer/session/start', static fn (Klockwork $kw) => $kw->solo->timer->session->start(['clientId' => $id]));
        $add('solo timer session save', 'POST', '/api/public/solo/timer/session/save', static fn (Klockwork $kw) => $kw->solo->timer->session->save(['summary' => 'Done']));
        $add('solo timer session discard', 'POST', '/api/public/solo/timer/session/discard', static fn (Klockwork $kw) => $kw->solo->timer->session->discard(), 'none');
        $add('solo timer session resume', 'POST', '/api/public/solo/timer/session/resume', static fn (Klockwork $kw) => $kw->solo->timer->session->resume(), 'none');
        $add('solo timer session timeout', 'POST', '/api/public/solo/timer/session/timeout', static fn (Klockwork $kw) => $kw->solo->timer->session->timeout(), 'none');
        $add('solo timer break start', 'POST', '/api/public/solo/timer/session/break/start', static fn (Klockwork $kw) => $kw->solo->timer->session->break->start([
            'label' => 'Lunch',
            'plannedMinutes' => 30,
        ]));
        $add('solo timer break end', 'POST', '/api/public/solo/timer/session/break/end', static fn (Klockwork $kw) => $kw->solo->timer->session->break->end(), 'none');
        $add('solo timer sessions list', 'GET', '/api/public/solo/timer/sessions?from=a&to=b', static fn (Klockwork $kw) => $kw->solo->timer->sessions->list(['from' => 'a', 'to' => 'b']));
        $add('solo timer sessions create', 'POST', '/api/public/solo/timer/sessions', static fn (Klockwork $kw) => $kw->solo->timer->sessions->create($json));
        $add('solo timer sessions get', 'GET', "/api/public/solo/timer/sessions/{$id}", static fn (Klockwork $kw) => $kw->solo->timer->sessions->get($id));
        $add('solo timer sessions update', 'PATCH', "/api/public/solo/timer/sessions/{$id}", static fn (Klockwork $kw) => $kw->solo->timer->sessions->update($id, $json));
        $add('solo timer sessions remove', 'DELETE', "/api/public/solo/timer/sessions/{$id}", static fn (Klockwork $kw) => $kw->solo->timer->sessions->remove($id, ['reason' => 'duplicate']), '{"reason":"duplicate"}');
        $add('solo timer sessions events', 'GET', "/api/public/solo/timer/sessions/{$id}/events", static fn (Klockwork $kw) => $kw->solo->timer->sessions->events($id));
        $add('solo invoices list', 'GET', '/api/public/solo/invoices', static fn (Klockwork $kw) => $kw->solo->invoices->list());
        $add('solo invoices create', 'POST', '/api/public/solo/invoices', static fn (Klockwork $kw) => $kw->solo->invoices->create($json));
        $add('solo invoices get', 'GET', "/api/public/solo/invoices/{$id}", static fn (Klockwork $kw) => $kw->solo->invoices->get($id));
        $add('solo invoices update', 'PATCH', "/api/public/solo/invoices/{$id}", static fn (Klockwork $kw) => $kw->solo->invoices->update($id, $json));
        $add('solo invoices void', 'POST', "/api/public/solo/invoices/{$id}/void", static fn (Klockwork $kw) => $kw->solo->invoices->voidInvoice($id), 'empty');
        $add('solo invoices events', 'GET', "/api/public/solo/invoices/{$id}/events", static fn (Klockwork $kw) => $kw->solo->invoices->events($id));
        $add('solo invoices shares', 'GET', "/api/public/solo/invoices/{$id}/shares", static fn (Klockwork $kw) => $kw->solo->invoices->shares($id));
        $add('solo invoices create share', 'POST', "/api/public/solo/invoices/{$id}/shares", static fn (Klockwork $kw) => $kw->solo->invoices->createShare($id), 'empty');
        $add('solo invoices revoke share', 'POST', "/api/public/solo/invoices/shares/{$id}/revoke", static fn (Klockwork $kw) => $kw->solo->invoices->revokeShare($id), 'none');
        $add('solo invoices unbilled', 'GET', '/api/public/solo/invoices/unbilled', static fn (Klockwork $kw) => $kw->solo->invoices->unbilled());
        $add('solo timesheet shares list', 'GET', "/api/public/solo/timesheet/shares?clientId={$id}", static fn (Klockwork $kw) => $kw->solo->timesheet->shares->list(['clientId' => $id]));
        $add('solo timesheet shares create', 'POST', '/api/public/solo/timesheet/shares', static fn (Klockwork $kw) => $kw->solo->timesheet->shares->create($json));
        $add('solo timesheet shares revoke', 'POST', "/api/public/solo/timesheet/shares/{$id}/revoke", static fn (Klockwork $kw) => $kw->solo->timesheet->shares->revoke($id), 'none');
        $add('solo payments list', 'GET', '/api/public/solo/payments', static fn (Klockwork $kw) => $kw->solo->payments->list());
        $add('solo payments create', 'POST', '/api/public/solo/payments', static fn (Klockwork $kw) => $kw->solo->payments->create($json));
        $add('solo payments get', 'GET', "/api/public/solo/payments/{$id}", static fn (Klockwork $kw) => $kw->solo->payments->get($id));
        $add('solo payments upload', 'POST', "/api/public/solo/payments/{$id}/attachments", static fn (Klockwork $kw) => $kw->solo->payments->uploadAttachment($id, 'proof', ['filename' => 'proof.pdf']), 'multipart');
        $add('solo payments delete attachment', 'DELETE', "/api/public/solo/payments/{$id}/attachments/{$id}", static fn (Klockwork $kw) => $kw->solo->payments->deleteAttachment($id, $id), 'none');
        $add('solo payments attachment url', 'GET', "/api/public/solo/payments/{$id}/attachments/{$id}/url", static fn (Klockwork $kw) => $kw->solo->payments->attachmentUrl($id, $id));
        $add('solo overview', 'GET', '/api/public/solo/overview', static fn (Klockwork $kw) => $kw->solo->overview->get());
        $add('solo billing get', 'GET', '/api/public/solo/billing', static fn (Klockwork $kw) => $kw->solo->billing->get());
        $add('solo billing sync', 'POST', '/api/public/solo/billing', static fn (Klockwork $kw) => $kw->solo->billing->sync(), 'none');
        $add('solo business profile get', 'GET', '/api/public/solo/business-profile', static fn (Klockwork $kw) => $kw->solo->businessProfile->get());
        $add('solo business profile update', 'PUT', '/api/public/solo/business-profile', static fn (Klockwork $kw) => $kw->solo->businessProfile->update($json));
        $add('solo invoice defaults get', 'GET', '/api/public/solo/invoice-defaults', static fn (Klockwork $kw) => $kw->solo->invoiceDefaults->get());
        $add('solo invoice defaults update', 'PUT', '/api/public/solo/invoice-defaults', static fn (Klockwork $kw) => $kw->solo->invoiceDefaults->update($json));
        $add('solo preferences get', 'GET', '/api/public/solo/preferences', static fn (Klockwork $kw) => $kw->solo->preferences->get());
        $add('solo preferences update', 'PUT', '/api/public/solo/preferences', static fn (Klockwork $kw) => $kw->solo->preferences->update($json));
        $add('solo notification settings get', 'GET', '/api/public/solo/notification-settings', static fn (Klockwork $kw) => $kw->solo->notificationSettings->get());
        $add('solo notification settings update', 'PUT', '/api/public/solo/notification-settings', static fn (Klockwork $kw) => $kw->solo->notificationSettings->update($json));
        $add('solo payout settings get', 'GET', '/api/public/solo/payout-settings', static fn (Klockwork $kw) => $kw->solo->payoutSettings->get());
        $add('solo payout settings update', 'PUT', '/api/public/solo/payout-settings', static fn (Klockwork $kw) => $kw->solo->payoutSettings->update($json));
        $add('solo slack connections', 'GET', '/api/public/solo/integrations/slack', static fn (Klockwork $kw) => $kw->solo->integrations->slack->connections());
        $add('solo slack channels', 'GET', "/api/public/solo/integrations/slack/channels?installId={$id}", static fn (Klockwork $kw) => $kw->solo->integrations->slack->channels(['installId' => $id]));
        $add('solo slack create binding', 'POST', '/api/public/solo/integrations/slack', static fn (Klockwork $kw) => $kw->solo->integrations->slack->createBinding($json));
        $add('solo slack update binding', 'PATCH', "/api/public/solo/integrations/slack/bindings/{$id}", static fn (Klockwork $kw) => $kw->solo->integrations->slack->updateBinding($id, $json));
        $add('solo slack remove binding', 'DELETE', "/api/public/solo/integrations/slack/bindings/{$id}", static fn (Klockwork $kw) => $kw->solo->integrations->slack->removeBinding($id), 'none');
        $add('solo slack disconnect', 'DELETE', "/api/public/solo/integrations/slack/installs/{$id}", static fn (Klockwork $kw) => $kw->solo->integrations->slack->disconnectInstall($id), 'none');
        $add('solo slack start test', 'POST', "/api/public/solo/integrations/slack/bindings/{$id}/test", static fn (Klockwork $kw) => $kw->solo->integrations->slack->startTest($id), 'none');
        $add('solo slack get test', 'GET', "/api/public/solo/integrations/slack/bindings/{$id}/test/{$id}", static fn (Klockwork $kw) => $kw->solo->integrations->slack->getTest($id, $id));
        $add('solo teams connections', 'GET', '/api/public/solo/integrations/teams', static fn (Klockwork $kw) => $kw->solo->integrations->teams->connections());
        $add('solo teams channels', 'GET', "/api/public/solo/integrations/teams/channels?installId={$id}", static fn (Klockwork $kw) => $kw->solo->integrations->teams->channels(['installId' => $id]));
        $add('solo teams create binding', 'POST', '/api/public/solo/integrations/teams', static fn (Klockwork $kw) => $kw->solo->integrations->teams->createBinding($json));
        $add('solo teams update binding', 'PATCH', "/api/public/solo/integrations/teams/bindings/{$id}", static fn (Klockwork $kw) => $kw->solo->integrations->teams->updateBinding($id, $json));
        $add('solo teams remove binding', 'DELETE', "/api/public/solo/integrations/teams/bindings/{$id}", static fn (Klockwork $kw) => $kw->solo->integrations->teams->removeBinding($id), 'none');
        $add('solo teams disconnect', 'DELETE', "/api/public/solo/integrations/teams/installs/{$id}", static fn (Klockwork $kw) => $kw->solo->integrations->teams->disconnectInstall($id), 'none');

        $add('company invoices list', 'GET', '/api/public/company/invoices', static fn (Klockwork $kw) => $kw->company->invoices->list());
        $add('company invoices get', 'GET', "/api/public/company/invoices/{$id}", static fn (Klockwork $kw) => $kw->company->invoices->get($id));
        $add('company talents list', 'GET', '/api/public/company/talents', static fn (Klockwork $kw) => $kw->company->talents->list());
        $add('company time list', 'GET', '/api/public/company/time?from=2026-01-01&to=2026-02-01', static fn (Klockwork $kw) => $kw->company->time->list([
            'from' => '2026-01-01',
            'to' => '2026-02-01',
        ]));

        return $routes;
    }
}
