<?php

declare(strict_types=1);

namespace Klockwork\Tests;

use Klockwork\Klockwork;
use Klockwork\TransportResponse;
use PHPUnit\Framework\TestCase;

final class AgencyClientActivateTest extends TestCase
{
    private const KEY = 'kw_test_secret';

    private const ID = '00000000-0000-4000-8000-000000000001';

    public function testActivatesAClientWithoutCovering(): void
    {
        $mock = $this->transport($this->jsonResponse([
            'client' => [
                'id' => self::ID,
                'adminActivated' => true,
                'seatStatus' => 'active',
                'invitationStatus' => 'pending',
                'inviteToken' => 'tok_live',
                'clientClerkOrgId' => null,
            ],
        ]));

        $result = $this->client($mock)->agency->clients->activate(self::ID);

        $call = $mock->calls[0];
        $this->assertSame('POST', $call['method']);
        $this->assertSame(
            'https://api.klockwork.org/api/public/agency/clients/' . self::ID . '/activate',
            $call['url'],
        );
        $this->assertNull($call['body']);
        $this->assertNull($this->header($call['headers'], 'content-type'));
        $this->assertTrue($result['client']['adminActivated']);
        $this->assertSame('active', $result['client']['seatStatus']);
        $this->assertSame('pending', $result['client']['invitationStatus']);
        $this->assertSame('tok_live', $result['client']['inviteToken']);
        $this->assertNull($result['client']['clientClerkOrgId']);
        $this->assertStringNotContainsString('/cover', $call['url']);
    }

    public function testInviteOmitsBodyByDefaultSoPublicApiSkipsEmail(): void
    {
        $mock = $this->transport($this->jsonResponse([
            'invitationSent' => false,
            'inviteUrl' => 'https://app.klockwork.org/client-invites/tok',
        ]));

        $result = $this->client($mock)->agency->clients->invite(self::ID);

        $call = $mock->calls[0];
        $this->assertSame('POST', $call['method']);
        $this->assertSame(
            'https://api.klockwork.org/api/public/agency/clients/' . self::ID . '/invite',
            $call['url'],
        );
        $this->assertNull($call['body']);
        $this->assertNull($this->header($call['headers'], 'content-type'));
        $this->assertFalse($result['invitationSent']);
    }

    public function testInviteCanRequestTokenOnlyWithoutEmail(): void
    {
        $mock = $this->transport($this->jsonResponse([
            'invitationSent' => false,
            'inviteUrl' => 'https://app.klockwork.org/client-invites/tok',
        ]));

        $result = $this->client($mock)->agency->clients->invite(self::ID, [
            'sendEmail' => false,
        ]);

        $call = $mock->calls[0];
        $this->assertSame('{"sendEmail":false}', $call['body']);
        $this->assertSame('application/json', $this->header($call['headers'], 'content-type'));
        $this->assertFalse($result['invitationSent']);
        $this->assertSame('https://app.klockwork.org/client-invites/tok', $result['inviteUrl']);
    }

    public function testInviteCanRequestBrandedEmail(): void
    {
        $mock = $this->transport($this->jsonResponse([
            'invitationSent' => true,
            'inviteUrl' => 'https://app.klockwork.org/client-invites/tok',
        ]));

        $result = $this->client($mock)->agency->clients->invite(self::ID, [
            'sendEmail' => true,
        ]);

        $this->assertSame('{"sendEmail":true}', $mock->calls[0]['body']);
        $this->assertTrue($result['invitationSent']);
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
