<?php

declare(strict_types=1);

namespace Klockwork\Tests;

use Klockwork\Klockwork;
use Klockwork\KlockworkError;
use Klockwork\PartnerCustomer;
use Klockwork\PartnerCustomerCreated;
use Klockwork\PartnerCustomerEnded;
use Klockwork\PartnerMe;
use Klockwork\TransportResponse;
use PHPUnit\Framework\TestCase;

final class PartnerApiTest extends TestCase
{
    private const KEY = 'kw_partner_test_secret';

    private const CUSTOMER_ID = '00000000-0000-4000-8000-000000000010';

    public function testMeReturnsPartnerStatus(): void
    {
        $mock = $this->transport($this->jsonResponse([
            'partnerId' => '00000000-0000-4000-8000-000000000099',
            'status' => 'active',
            'subscriptionExempt' => false,
            'orgId' => 'org_partner',
        ]));

        $me = $this->client($mock)->partner->me();

        $this->assertInstanceOf(PartnerMe::class, $me);
        $this->assertSame('00000000-0000-4000-8000-000000000099', $me->partnerId);
        $this->assertSame('active', $me->status);
        $this->assertFalse($me->subscriptionExempt);
        $this->assertSame('org_partner', $me->orgId);
        $this->assertSame('GET', $mock->calls[0]['method']);
        $this->assertSame('https://api.klockwork.org/api/public/partner/me', $mock->calls[0]['url']);
        $this->assertSame(self::KEY, $this->header($mock->calls[0]['headers'], 'x-klockwork-key'));
        $this->assertNull($this->header($mock->calls[0]['headers'], 'x-klockwork-talent'));
        $this->assertNull($this->header($mock->calls[0]['headers'], 'x-klockwork-client'));
    }

    public function testListsCustomersEmptyAndNonEmpty(): void
    {
        $empty = $this->transport($this->jsonResponse(['customers' => []]));
        $this->assertSame([], $this->client($empty)->partner->customers->list());

        $mock = $this->transport($this->jsonResponse([
            'customers' => [
                [
                    'id' => self::CUSTOMER_ID,
                    'companyName' => 'Northwind',
                    'invoiceEmail' => 'billing@northwind.example',
                    'status' => 'pending',
                    'customerClerkOrgId' => null,
                    'associatedAt' => null,
                    'inviteUrl' => 'https://app.klockwork.org/partner-invites/token',
                ],
                [
                    'id' => '00000000-0000-4000-8000-000000000011',
                    'companyName' => 'Acme',
                    'invoiceEmail' => 'ada@acme.test',
                    'status' => 'active',
                    'customerClerkOrgId' => 'org_acme',
                    'associatedAt' => '2026-10-01T12:00:00.000Z',
                ],
            ],
        ]));

        $customers = $this->client($mock)->partner->customers->list();

        $this->assertCount(2, $customers);
        $this->assertContainsOnlyInstancesOf(PartnerCustomer::class, $customers);
        $this->assertSame(self::CUSTOMER_ID, $customers[0]->id);
        $this->assertSame('pending', $customers[0]->status);
        $this->assertSame('https://app.klockwork.org/partner-invites/token', $customers[0]->inviteUrl);
        $this->assertNull($customers[0]->customerClerkOrgId);
        $this->assertNull($customers[0]->associatedAt);
        $this->assertSame('active', $customers[1]->status);
        $this->assertNull($customers[1]->inviteUrl);
        $this->assertSame('org_acme', $customers[1]->customerClerkOrgId);
        $this->assertSame('2026-10-01T12:00:00.000Z', $customers[1]->associatedAt);
    }

    public function testCreateInviteReturnsInviteUrlAndInvitationSent(): void
    {
        $mock = $this->transport($this->jsonResponse([
            'customer' => [
                'id' => self::CUSTOMER_ID,
                'companyName' => 'Northwind',
                'invoiceEmail' => 'billing@northwind.example',
                'status' => 'pending',
                'customerClerkOrgId' => null,
                'associatedAt' => null,
                'inviteUrl' => 'https://app.klockwork.org/partner-invites/token',
            ],
            'inviteUrl' => 'https://app.klockwork.org/partner-invites/token',
            'invitationSent' => true,
        ], 201));

        $created = $this->client($mock)->partner->customers->create('Northwind', 'billing@northwind.example');

        $this->assertInstanceOf(PartnerCustomerCreated::class, $created);
        $this->assertSame(self::CUSTOMER_ID, $created->customer->id);
        $this->assertSame('Northwind', $created->customer->companyName);
        $this->assertSame('https://app.klockwork.org/partner-invites/token', $created->inviteUrl);
        $this->assertTrue($created->invitationSent);
        $this->assertSame(
            '{"companyName":"Northwind","invoiceEmail":"billing@northwind.example"}',
            $mock->calls[0]['body'],
        );
    }

    public function testEndsACustomerRelationship(): void
    {
        $mock = $this->transport($this->jsonResponse(['ok' => true]));

        $ended = $this->client($mock)->partner->customers->end(self::CUSTOMER_ID);

        $this->assertInstanceOf(PartnerCustomerEnded::class, $ended);
        $this->assertTrue($ended->ok);
        $this->assertSame('POST', $mock->calls[0]['method']);
        $this->assertSame(
            'https://api.klockwork.org/api/public/partner/customers/' . self::CUSTOMER_ID . '/end',
            $mock->calls[0]['url'],
        );
        $this->assertNull($mock->calls[0]['body']);
    }

    public function testMissingScopeReturns403(): void
    {
        $mock = $this->transport($this->jsonResponse(['error' => 'Missing scope'], 403));

        try {
            $this->client($mock)->partner->customers->list();
            $this->fail('Expected a missing scope to be rejected.');
        } catch (KlockworkError $error) {
            $this->assertSame(403, $error->status);
            $this->assertSame('Missing scope', $error->error);
        }
    }

    public function testInactivePartnerReturns403(): void
    {
        $mock = $this->transport($this->jsonResponse(['error' => 'Partner is not active'], 403));

        try {
            $this->client($mock)->partner->me();
            $this->fail('Expected an inactive partner to be rejected.');
        } catch (KlockworkError $error) {
            $this->assertSame(403, $error->status);
            $this->assertSame('Partner is not active', $error->error);
        }
    }

    public function testOpenInviteConflictReturns409(): void
    {
        $mock = $this->transport($this->jsonResponse(
            ['error' => 'An invite for this email is already open'],
            409,
        ));

        try {
            $this->client($mock)->partner->customers->create('Northwind', 'billing@northwind.example');
            $this->fail('Expected a duplicate invite to be rejected.');
        } catch (KlockworkError $error) {
            $this->assertSame(409, $error->status);
            $this->assertSame('An invite for this email is already open', $error->error);
        }
    }

    public function testRateLimitReturns429(): void
    {
        $mock = $this->transport($this->jsonResponse(['error' => 'Rate limit exceeded'], 429));

        try {
            $this->client($mock)->partner->customers->list();
            $this->fail('Expected a rate limit to be rejected.');
        } catch (KlockworkError $error) {
            $this->assertSame(429, $error->status);
            $this->assertSame('Rate limit exceeded', $error->error);
        }
    }

    public function testPartnerKeyAgainstAgencyRouteSurfaces401(): void
    {
        $mock = $this->transport($this->jsonResponse(['error' => 'Invalid API key'], 401));

        try {
            $this->client($mock)->agency->clients->list();
            $this->fail('Expected a partner key on an agency route to be rejected.');
        } catch (KlockworkError $error) {
            $this->assertSame(401, $error->status);
            $this->assertSame('Invalid API key', $error->error);
        }

        $this->assertSame('https://api.klockwork.org/api/public/agency/clients', $mock->calls[0]['url']);
    }

    public function testInvalidPartnerMePayloadThrows(): void
    {
        $mock = $this->transport($this->jsonResponse(['status' => 'active']));

        try {
            $this->client($mock)->partner->me();
            $this->fail('Expected an incomplete partner me payload to be rejected.');
        } catch (KlockworkError $error) {
            $this->assertSame(200, $error->status);
            $this->assertMatchesRegularExpression('/partnerId|Invalid/', $error->error);
        }
    }

    /**
     * @param array<string, mixed> $body
     */
    private function jsonResponse(array $body, int $status = 200): TransportResponse
    {
        return new TransportResponse(
            $status,
            $status >= 400 ? 'Error' : 'OK',
            json_encode($body, JSON_THROW_ON_ERROR),
        );
    }

    private function transport(TransportResponse $response): RecordingTransport
    {
        return new RecordingTransport($response);
    }

    /**
     * @param array<string, mixed> $options
     */
    private function client(RecordingTransport $transport, array $options = []): Klockwork
    {
        $options['apiKey'] ??= self::KEY;
        $options['transport'] = $transport;

        return new Klockwork($options);
    }

    /**
     * @param array<string, string> $headers
     */
    private function header(array $headers, string $name): ?string
    {
        foreach ($headers as $key => $value) {
            if (strcasecmp($key, $name) === 0) {
                return $value;
            }
        }

        return null;
    }
}
