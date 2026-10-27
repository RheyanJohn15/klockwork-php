<?php

declare(strict_types=1);

namespace Klockwork\Tests;

use Klockwork\Klockwork;
use Klockwork\KlockworkError;
use Klockwork\TransportResponse;
use PHPUnit\Framework\TestCase;

final class CompanyBillRateTest extends TestCase
{
    private const KEY = 'kw_test_secret';

    private const TALENT = 'user_abc';

    private const CLIENT = '00000000-0000-4000-8000-000000000002';

    private const ASSIGNMENT = '00000000-0000-4000-8000-000000000003';

    public function testCreatesAnIdentityOnlyAgencyClient(): void
    {
        $mock = $this->transport($this->jsonResponse([
            'client' => [
                'id' => self::CLIENT,
                'displayName' => 'Acme',
                'currency' => 'PHP',
                'useClientDefaults' => false,
                'hourlyRate' => null,
                'hoursPerDay' => null,
                'shiftStart' => null,
                'shiftEnd' => null,
                'timezone' => null,
            ],
        ], 201));

        $created = $this->client($mock)->agency->clients->create([
            'displayName' => 'Acme',
            'contactPersonName' => 'Ada',
            'invoiceEmail' => 'ada@acme.test',
            'currency' => 'PHP',
            'useClientDefaults' => false,
        ]);

        $this->assertSame(
            '{"displayName":"Acme","contactPersonName":"Ada","invoiceEmail":"ada@acme.test","currency":"PHP","useClientDefaults":false}',
            $mock->calls[0]['body'],
        );
        $this->assertFalse($created['client']['useClientDefaults']);
        $this->assertNull($created['client']['hourlyRate']);
        $this->assertNull($created['client']['hoursPerDay']);
        $this->assertNull($created['client']['shiftStart']);
        $this->assertNull($created['client']['shiftEnd']);
        $this->assertNull($created['client']['timezone']);
        $this->assertSame('PHP', $created['client']['currency']);
    }

    public function testCreatesAnAgencyClientThatRequiresDefaultRateFields(): void
    {
        $mock = $this->transport($this->jsonResponse(['client' => ['id' => self::CLIENT]], 201));

        $this->client($mock)->agency->clients->create([
            'displayName' => 'Acme',
            'contactPersonName' => 'Ada',
            'invoiceEmail' => 'ada@acme.test',
            'currency' => 'PHP',
            'useClientDefaults' => true,
            'hourlyRate' => 150,
            'hoursPerDay' => 8,
            'shiftStart' => '09:00',
            'shiftEnd' => '18:00',
            'timezone' => 'Asia/Manila',
        ]);

        $this->assertSame(
            '{"displayName":"Acme","contactPersonName":"Ada","invoiceEmail":"ada@acme.test","currency":"PHP","useClientDefaults":true,"hourlyRate":150,"hoursPerDay":8,"shiftStart":"09:00","shiftEnd":"18:00","timezone":"Asia/Manila"}',
            $mock->calls[0]['body'],
        );
    }

    public function testAssignsATalentWithAndWithoutAPlacementRate(): void
    {
        $mock = new RecordingTransport($this->jsonResponse(['assignment' => ['id' => self::ASSIGNMENT]], 201));
        $agency = $this->client($mock)->agency;

        $agency->assignments->create([
            'clientId' => self::CLIENT,
            'talentId' => self::TALENT,
        ]);
        $agency->assignments->create([
            'clientId' => self::CLIENT,
            'talentId' => self::TALENT,
            'hourlyRate' => 220,
        ]);
        $agency->assignments->update(self::ASSIGNMENT, ['hourlyRate' => null]);

        $this->assertSame(
            '{"clientId":"' . self::CLIENT . '","talentId":"' . self::TALENT . '"}',
            $mock->calls[0]['body'],
        );
        $this->assertSame(
            '{"clientId":"' . self::CLIENT . '","talentId":"' . self::TALENT . '","hourlyRate":220}',
            $mock->calls[1]['body'],
        );
        $this->assertSame(
            'PATCH',
            $mock->calls[2]['method'],
        );
        $this->assertSame(
            'https://klockwork.org/api/public/agency/assignments/' . self::ASSIGNMENT,
            $mock->calls[2]['url'],
        );
        $this->assertSame('{"hourlyRate":null}', $mock->calls[2]['body']);
    }

    public function testTalentPatchesTheirOwnPlacementRate(): void
    {
        $mock = $this->transport($this->jsonResponse([
            'placement' => [
                'id' => self::ASSIGNMENT,
                'hourlyRate' => 175,
                'clientUseDefaults' => false,
                'clientDefaultHourlyRate' => null,
            ],
        ]));

        $updated = $this->client($mock, ['talentId' => self::TALENT])->solo->placements->update(self::ASSIGNMENT, [
            'hourlyRate' => 175,
        ]);

        $call = $mock->calls[0];
        $this->assertSame('PATCH', $call['method']);
        $this->assertSame(
            'https://klockwork.org/api/public/solo/placements/' . self::ASSIGNMENT,
            $call['url'],
        );
        $this->assertSame(self::TALENT, $this->header($call['headers'], 'x-klockwork-talent'));
        $this->assertSame('{"hourlyRate":175}', $call['body']);
        $this->assertSame(175, $updated['placement']['hourlyRate']);
        $this->assertFalse($updated['placement']['clientUseDefaults']);
        $this->assertNull($updated['placement']['clientDefaultHourlyRate']);
    }

    public function testSurfacesAMissingBillRateWhenStartingCompanyLinkedTime(): void
    {
        $message = 'Set a placement rate or enable a client default rate before recording time.';
        $mock = $this->transport($this->jsonResponse(['error' => $message], 400));

        try {
            $this->client($mock, ['talentId' => self::TALENT])->solo->timer->session->start([
                'orgClientId' => self::CLIENT,
            ]);
            $this->fail('Expected a missing bill rate to be rejected.');
        } catch (KlockworkError $error) {
            $this->assertSame(400, $error->status);
            $this->assertSame($message, $error->error);
        }

        $this->assertSame(
            '{"orgClientId":"' . self::CLIENT . '"}',
            $mock->calls[0]['body'],
        );
    }

    public function testSurfacesAMissingBillRateOnAnAgencyInvoice(): void
    {
        $message = 'Ada has no bill rate for Acme. Set a placement rate or enable a client default rate.';
        $mock = $this->transport($this->jsonResponse(['error' => $message], 400));

        try {
            $this->client($mock)->agency->invoices->create([
                'clientId' => self::CLIENT,
                'from' => '2026-10-01',
                'to' => '2026-10-31',
            ]);
            $this->fail('Expected an unresolved bill rate to be rejected.');
        } catch (KlockworkError $error) {
            $this->assertSame(400, $error->status);
            $this->assertSame($message, $error->error);
        }

        $this->assertSame(
            '{"clientId":"' . self::CLIENT . '","from":"2026-10-01","to":"2026-10-31"}',
            $mock->calls[0]['body'],
        );
        $this->assertStringNotContainsString('hourlyRate', (string) $mock->calls[0]['body']);
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
