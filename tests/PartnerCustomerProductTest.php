<?php

declare(strict_types=1);

namespace Klockwork\Tests;

use Klockwork\Klockwork;
use Klockwork\KlockworkError;
use Klockwork\TransportResponse;
use PHPUnit\Framework\TestCase;

final class PartnerCustomerProductTest extends TestCase
{
    private const KEY = 'kw_partner_test_secret';

    private const CUSTOMER = 'org_customer_abc';

    private const TALENT = 'user_abc';

    private const CLIENT = '00000000-0000-4000-8000-000000000002';

    public function testConstructorCustomerIdSendsHeaderOnAgencyCallsOnly(): void
    {
        $mock = new SequencedRecordingTransport([
            $this->jsonResponse(['clients' => []]),
            $this->jsonResponse([
                'partnerId' => '00000000-0000-4000-8000-000000000099',
                'status' => 'active',
                'subscriptionExempt' => false,
                'orgId' => 'org_partner',
            ]),
        ]);
        $kw = $this->client($mock, ['customerId' => self::CUSTOMER]);

        $kw->agency->clients->list();
        $kw->partner->me();

        $this->assertSame(self::CUSTOMER, $this->header($mock->calls[0]['headers'], 'x-klockwork-customer'));
        $this->assertSame(
            'https://api.klockwork.org/api/public/agency/clients',
            $mock->calls[0]['url'],
        );
        $this->assertNull($this->header($mock->calls[1]['headers'], 'x-klockwork-customer'));
        $this->assertSame(
            'https://api.klockwork.org/api/public/partner/me',
            $mock->calls[1]['url'],
        );
    }

    public function testForCustomerSendsCustomerHeaderOnProductCalls(): void
    {
        $mock = $this->transport($this->jsonResponse(['clients' => []]));
        $as = $this->client($mock)->partner->forCustomer(self::CUSTOMER);

        $as->agency->clients->list();
        $as->solo->forTalent(self::TALENT)->clients->list();
        $as->company->forClient(self::CLIENT)->invoices->list();

        $this->assertSame(self::CUSTOMER, $this->header($mock->calls[0]['headers'], 'x-klockwork-customer'));
        $this->assertNull($this->header($mock->calls[0]['headers'], 'x-klockwork-talent'));
        $this->assertNull($this->header($mock->calls[0]['headers'], 'x-klockwork-client'));

        $this->assertSame(self::CUSTOMER, $this->header($mock->calls[1]['headers'], 'x-klockwork-customer'));
        $this->assertSame(self::TALENT, $this->header($mock->calls[1]['headers'], 'x-klockwork-talent'));
        $this->assertSame(
            'https://api.klockwork.org/api/public/solo/clients',
            $mock->calls[1]['url'],
        );

        $this->assertSame(self::CUSTOMER, $this->header($mock->calls[2]['headers'], 'x-klockwork-customer'));
        $this->assertSame(self::CLIENT, $this->header($mock->calls[2]['headers'], 'x-klockwork-client'));
        $this->assertSame(
            'https://api.klockwork.org/api/public/company/invoices',
            $mock->calls[2]['url'],
        );
    }

    public function testForCustomerDoesNotAffectUnboundPartnerRelationshipCalls(): void
    {
        $mock = $this->transport($this->jsonResponse([
            'partnerId' => '00000000-0000-4000-8000-000000000099',
            'status' => 'active',
            'subscriptionExempt' => false,
            'orgId' => 'org_partner',
        ]));
        $kw = $this->client($mock);
        $kw->partner->forCustomer(self::CUSTOMER);
        $kw->partner->me();

        $this->assertNull($this->header($mock->calls[0]['headers'], 'x-klockwork-customer'));
    }

    public function testMissingCustomerHeaderSurfaces400(): void
    {
        $mock = $this->transport($this->jsonResponse(
            ['error' => 'X-Klockwork-Customer is required'],
            400,
        ));

        try {
            $this->client($mock)->agency->clients->list();
            $this->fail('Expected a missing customer header to be rejected.');
        } catch (KlockworkError $error) {
            $this->assertSame(400, $error->status);
            $this->assertSame('X-Klockwork-Customer is required', $error->error);
        }

        $this->assertNull($this->header($mock->calls[0]['headers'], 'x-klockwork-customer'));
    }

    public function testMissingProductScopeSurfaces403(): void
    {
        $mock = $this->transport($this->jsonResponse(['error' => 'Missing scope'], 403));

        try {
            $this->client($mock)->partner->forCustomer(self::CUSTOMER)->agency->clients->list();
            $this->fail('Expected a missing scope to be rejected.');
        } catch (KlockworkError $error) {
            $this->assertSame(403, $error->status);
            $this->assertSame('Missing scope', $error->error);
        }
    }

    public function testPendingCustomerSurfaces403(): void
    {
        $message = 'This customer has not accepted the partnership yet';
        $mock = $this->transport($this->jsonResponse(['error' => $message], 403));

        try {
            $this->client($mock)->partner->forCustomer(self::CUSTOMER)->agency->clients->list();
            $this->fail('Expected a pending customer to be rejected.');
        } catch (KlockworkError $error) {
            $this->assertSame(403, $error->status);
            $this->assertSame($message, $error->error);
        }
    }

    public function testWrongCustomerSurfaces403(): void
    {
        $message = 'This company is not an active customer of the partner';
        $mock = $this->transport($this->jsonResponse(['error' => $message], 403));

        try {
            $this->client($mock)->partner->forCustomer(self::CUSTOMER)->agency->clients->list();
            $this->fail('Expected a wrong customer to be rejected.');
        } catch (KlockworkError $error) {
            $this->assertSame(403, $error->status);
            $this->assertSame($message, $error->error);
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
    private function client(RecordingTransport|SequencedRecordingTransport $transport, array $options = []): Klockwork
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

final class SequencedRecordingTransport
{
    /**
     * @var list<array{url: string, method: string, headers: array<string, string>, body: string|array<string, mixed>|null}>
     */
    public array $calls = [];

    private int $index = 0;

    /**
     * @param list<TransportResponse> $responses
     */
    public function __construct(private readonly array $responses)
    {
    }

    /**
     * @param array<string, string> $headers
     * @param string|array<string, mixed>|null $body
     */
    public function __invoke(string $method, string $url, array $headers, string|array|null $body): TransportResponse
    {
        $this->calls[] = [
            'url' => $url,
            'method' => $method,
            'headers' => $headers,
            'body' => $body,
        ];

        $response = $this->responses[$this->index] ?? null;
        if ($response === null) {
            throw new \RuntimeException('No more sequenced transport responses.');
        }
        $this->index++;

        return $response;
    }
}
