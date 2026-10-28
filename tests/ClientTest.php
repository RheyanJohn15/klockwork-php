<?php

declare(strict_types=1);

namespace Klockwork\Tests;

use Klockwork\Klockwork;
use Klockwork\KlockworkError;
use Klockwork\TransportResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ClientTest extends TestCase
{
    private const KEY = 'kw_test_secret';

    private const TALENT = 'user_abc';

    private const CLIENT = '00000000-0000-4000-8000-000000000002';

    private const ID = '00000000-0000-4000-8000-000000000001';

    public function testRejectsAMissingOrNonKwKey(): void
    {
        foreach (['', 'sk_live'] as $apiKey) {
            try {
                new Klockwork(['apiKey' => $apiKey]);
                $this->fail('Expected an invalid API key to be rejected.');
            } catch (\InvalidArgumentException $error) {
                $this->assertMatchesRegularExpression('/kw_/', $error->getMessage());
            }
        }
    }

    public function testUsesProductionByDefaultLocalhostForLocalAndBaseUrlWhenSet(): void
    {
        $prod = $this->transport($this->jsonResponse(['clients' => []]));
        $this->client($prod)->agency->clients->list();
        $this->assertSame(
            'https://api.klockwork.org/api/public/agency/clients',
            $prod->calls[0]['url'],
        );

        $local = $this->transport($this->jsonResponse(['clients' => []]));
        $this->client($local, ['environment' => 'local'])->agency->clients->list();
        $this->assertSame(
            'http://localhost:3001/api/public/agency/clients',
            $local->calls[0]['url'],
        );

        $custom = $this->transport($this->jsonResponse(['clients' => []]));
        $this->client($custom, [
            'environment' => 'local',
            'baseUrl' => 'https://preview.example/',
        ])->agency->clients->list();
        $this->assertSame(
            'https://preview.example/api/public/agency/clients',
            $custom->calls[0]['url'],
        );
    }

    public function testSendsKeyAndJsonHeadersOnAgencyCalls(): void
    {
        $mock = $this->transport($this->jsonResponse(['client' => ['id' => self::ID]], 201));
        $this->client($mock)->agency->clients->create([
            'displayName' => 'Acme',
            'contactPersonName' => 'Ada',
            'invoiceEmail' => 'ada@acme.test',
            'currency' => 'PHP',
            'useClientDefaults' => true,
            'hourlyRate' => 100,
            'hoursPerDay' => 8,
            'shiftStart' => '09:00',
            'shiftEnd' => '18:00',
            'timezone' => 'Asia/Manila',
        ]);

        $call = $mock->calls[0];
        $this->assertSame('POST', $call['method']);
        $this->assertSame(self::KEY, $this->header($call['headers'], 'x-klockwork-key'));
        $this->assertSame('application/json', $this->header($call['headers'], 'accept'));
        $this->assertSame('application/json', $this->header($call['headers'], 'content-type'));
        $this->assertSame('klockwork-php/0.4.0', $this->header($call['headers'], 'user-agent'));
        $this->assertNull($this->header($call['headers'], 'x-klockwork-talent'));
        $this->assertNull($this->header($call['headers'], 'x-klockwork-client'));
        $this->assertIsString($call['body']);
        $body = json_decode($call['body'], true);
        $this->assertSame('Acme', $body['displayName']);
    }

    public function testSendsTheTalentHeaderFromForTalentTheConstructorOrTheCall(): void
    {
        $mock = $this->transport($this->jsonResponse(['clients' => []]));
        $kw = $this->client($mock, ['talentId' => 'user_default']);
        $kw->solo->clients->list();
        $kw->solo->forTalent(self::TALENT)->clients->list(['status' => 'all']);
        $kw->solo->clients->list(['talentId' => 'user_call', 'status' => 'archived']);

        $this->assertSame('user_default', $this->header($mock->calls[0]['headers'], 'x-klockwork-talent'));
        $this->assertSame(self::TALENT, $this->header($mock->calls[1]['headers'], 'x-klockwork-talent'));
        $this->assertSame(
            'https://api.klockwork.org/api/public/solo/clients?status=all',
            $mock->calls[1]['url'],
        );
        $this->assertSame('user_call', $this->header($mock->calls[2]['headers'], 'x-klockwork-talent'));
        $this->assertSame(self::KEY, $this->header($mock->calls[2]['headers'], 'x-klockwork-key'));
        $this->assertSame(
            'https://api.klockwork.org/api/public/solo/clients?status=archived',
            $mock->calls[2]['url'],
        );
    }

    public function testRequiresATalentIdBeforeCallingSolo(): void
    {
        $mock = $this->transport($this->jsonResponse([]));
        try {
            $this->client($mock)->solo->clients->list();
            $this->fail('Expected a missing talent id to be rejected.');
        } catch (\InvalidArgumentException $error) {
            $this->assertMatchesRegularExpression('/talent/i', $error->getMessage());
        }
        $this->assertSame([], $mock->calls);
    }

    public function testSendsTheClientHeaderAndTimeQueryForCompanyCalls(): void
    {
        $mock = $this->transport($this->jsonResponse(['summary' => [], 'byTalent' => [], 'entries' => []]));
        $this->client($mock)
            ->company
            ->forClient(self::CLIENT)
            ->time
            ->list(['from' => '2026-01-01', 'to' => '2026-02-01']);

        $call = $mock->calls[0];
        $this->assertSame(self::CLIENT, $this->header($call['headers'], 'x-klockwork-client'));
        $this->assertSame(self::KEY, $this->header($call['headers'], 'x-klockwork-key'));
        $this->assertSame(
            'https://api.klockwork.org/api/public/company/time?from=2026-01-01&to=2026-02-01',
            $call['url'],
        );
    }

    public function testUploadsPaymentProofAsMultipartWithoutAJsonContentType(): void
    {
        $mock = $this->transport($this->jsonResponse(['attachment' => ['id' => self::ID]], 201));
        $this->client($mock, ['talentId' => self::TALENT])->solo->payments->uploadAttachment(
            self::ID,
            'proof',
            ['filename' => 'proof.pdf', 'contentType' => 'application/pdf'],
        );

        $call = $mock->calls[0];
        $this->assertSame('POST', $call['method']);
        $this->assertSame(
            'https://api.klockwork.org/api/public/solo/payments/' . self::ID . '/attachments',
            $call['url'],
        );
        $this->assertNull($this->header($call['headers'], 'content-type'));
        $this->assertSame([
            'file' => [
                'filename' => 'proof.pdf',
                'contents' => 'proof',
                'contentType' => 'application/pdf',
            ],
        ], $call['body']);
    }

    public function testThrowsKlockworkErrorWithStatusErrorAndDetails(): void
    {
        $mock = $this->transport($this->jsonResponse(
            ['error' => 'Choose an organization', 'details' => ['code' => 'choose_org']],
            409,
        ));

        try {
            $this->client($mock)->agency->clients->list();
            $this->fail('Expected a KlockworkError.');
        } catch (KlockworkError $error) {
            $this->assertSame(409, $error->status);
            $this->assertSame('Choose an organization', $error->error);
            $this->assertSame(['code' => 'choose_org'], $error->details);
        }
    }

    public function testThrowsKlockworkErrorWhenTheErrorBodyIsNotJson(): void
    {
        $mock = $this->transport(new TransportResponse(502, 'Bad Gateway', 'nope'));

        try {
            $this->client($mock)->agency->clients->list();
            $this->fail('Expected a KlockworkError.');
        } catch (KlockworkError $error) {
            $this->assertSame(502, $error->status);
            $this->assertMatchesRegularExpression('/nope|502/', $error->error);
        }
    }

    #[DataProvider('routes')]
    public function testPublicRoute(string $name, string $method, string $path, \Closure $call, ?string $expectedBody = null): void
    {
        $mock = $this->transport($this->jsonResponse($this->routeFixture($path, $method)));
        $kw = $this->client($mock, [
            'talentId' => self::TALENT,
            'clientId' => self::CLIENT,
        ]);
        $call($kw);

        $httpCall = $mock->calls[0];
        $this->assertSame($method, $httpCall['method']);
        $this->assertSame('https://api.klockwork.org' . $path, $httpCall['url']);
        if (str_starts_with($path, '/api/public/solo')) {
            $this->assertSame(self::TALENT, $this->header($httpCall['headers'], 'x-klockwork-talent'));
        } else {
            $this->assertNull($this->header($httpCall['headers'], 'x-klockwork-talent'));
        }
        if (str_starts_with($path, '/api/public/company')) {
            $this->assertSame(self::CLIENT, $this->header($httpCall['headers'], 'x-klockwork-client'));
        } else {
            $this->assertNull($this->header($httpCall['headers'], 'x-klockwork-client'));
        }
        if ($expectedBody === 'none') {
            $this->assertNull($httpCall['body']);
            $this->assertNull($this->header($httpCall['headers'], 'content-type'));
        } elseif ($expectedBody === 'empty') {
            $this->assertSame('{}', $httpCall['body']);
        } elseif ($expectedBody === 'multipart') {
            $this->assertNull($this->header($httpCall['headers'], 'content-type'));
            $this->assertIsArray($httpCall['body']);
            $this->assertArrayHasKey('file', $httpCall['body']);
        } elseif (is_string($expectedBody)) {
            $this->assertSame($expectedBody, $httpCall['body']);
        }
    }

    /**
     * @return array<string, array{0: string, 1: string, 2: string, 3: \Closure, 4: ?string}>
     */
    public static function routes(): array
    {
        $cases = [];
        foreach (RouteCatalog::cases() as $name => [$method, $path, $call, $expectedBody]) {
            $cases[$name] = [$name, $method, $path, $call, $expectedBody];
        }

        return $cases;
    }

    public function testStripsTalentIdFromTheJsonBody(): void
    {
        $mock = $this->transport($this->jsonResponse(['client' => []]));
        $this->client($mock, ['talentId' => 'user_default'])->solo->clients->create([
            'talentId' => 'user_call',
            'displayName' => 'Acme',
        ]);

        $call = $mock->calls[0];
        $this->assertSame('user_call', $this->header($call['headers'], 'x-klockwork-talent'));
        $this->assertIsString($call['body']);
        $body = json_decode($call['body'], true);
        $this->assertSame(['displayName' => 'Acme'], $body);
    }

    public function testStripsClientIdFromTheCompanyTimeQuery(): void
    {
        $mock = $this->transport($this->jsonResponse(['summary' => [], 'byTalent' => [], 'entries' => []]));
        $this->client($mock)->company->forClient(self::CLIENT)->time->list([
            'clientId' => '00000000-0000-4000-8000-000000000099',
            'from' => '2026-01-01',
            'to' => '2026-02-01',
        ]);

        $call = $mock->calls[0];
        $this->assertSame('00000000-0000-4000-8000-000000000099', $this->header($call['headers'], 'x-klockwork-client'));
        $this->assertSame(
            'https://api.klockwork.org/api/public/company/time?from=2026-01-01&to=2026-02-01',
            $call['url'],
        );
    }

    public function testRequiresAClientIdBeforeCallingCompany(): void
    {
        $mock = $this->transport($this->jsonResponse([]));
        try {
            $this->client($mock)->company->invoices->list();
            $this->fail('Expected a missing client id to be rejected.');
        } catch (\InvalidArgumentException $error) {
            $this->assertMatchesRegularExpression('/client/i', $error->getMessage());
        }
        $this->assertSame([], $mock->calls);
    }

    public function testThrowsWhenASuccessBodyIsNotJson(): void
    {
        $mock = $this->transport(new TransportResponse(200, 'OK', 'not-json'));
        try {
            $this->client($mock)->partner->me();
            $this->fail('Expected a KlockworkError.');
        } catch (KlockworkError $error) {
            $this->assertSame(200, $error->status);
            $this->assertSame('Invalid JSON response', $error->error);
            $this->assertSame('not-json', $error->body);
        }
    }

    public function testOmitsNullQueryValues(): void
    {
        $mock = $this->transport($this->jsonResponse(['clients' => []]));
        $this->client($mock)->agency->clients->list(['status' => null, 'q' => 'acme']);
        $this->assertSame(
            'https://api.klockwork.org/api/public/agency/clients?q=acme',
            $mock->calls[0]['url'],
        );
    }

    public function testEncodesPathIds(): void
    {
        $mock = $this->transport($this->jsonResponse(['client' => []]));
        $this->client($mock)->agency->clients->get('a b/c');
        $this->assertSame(
            'https://api.klockwork.org/api/public/agency/clients/a%20b%2Fc',
            $mock->calls[0]['url'],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function routeFixture(string $path, string $method): array
    {
        if ($path === '/api/public/partner/me') {
            return [
                'partnerId' => self::ID,
                'status' => 'active',
                'subscriptionExempt' => false,
                'orgId' => 'org_partner',
            ];
        }
        if ($path === '/api/public/partner/customers' && $method === 'GET') {
            return ['customers' => []];
        }
        if ($path === '/api/public/partner/customers' && $method === 'POST') {
            return [
                'customer' => [
                    'id' => self::ID,
                    'companyName' => 'Acme',
                    'invoiceEmail' => 'ada@acme.test',
                    'status' => 'pending',
                    'customerClerkOrgId' => null,
                    'associatedAt' => null,
                    'inviteUrl' => 'https://app.klockwork.org/partner-invites/token',
                ],
                'inviteUrl' => 'https://app.klockwork.org/partner-invites/token',
                'invitationSent' => true,
            ];
        }
        if (str_ends_with($path, '/end') && str_contains($path, '/api/public/partner/customers/')) {
            return ['ok' => true];
        }

        return [];
    }

    private function jsonResponse(mixed $body, int $status = 200): TransportResponse
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

final class RecordingTransport
{
    /**
     * @var list<array{url: string, method: string, headers: array<string, string>, body: string|array<string, mixed>|null}>
     */
    public array $calls = [];

    public function __construct(private readonly TransportResponse $response)
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

        return $this->response;
    }
}
