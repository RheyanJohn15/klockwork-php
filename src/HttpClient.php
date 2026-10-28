<?php

declare(strict_types=1);

namespace Klockwork;

final class HttpClient
{
    /**
     * @param callable(string, string, array<string, string>, string|array<string, array{filename: string, contents: string, contentType: string}>|null): TransportResponse $transport
     */
    public function __construct(
        private readonly string $apiKey,
        private readonly string $baseUrl,
        private readonly mixed $transport,
        private readonly ?string $customerId = null,
    ) {
    }

    public function withCustomer(string $customerId): self
    {
        $trimmed = trim($customerId);
        if ($trimmed === '') {
            throw new \InvalidArgumentException('customerId must be a non-empty string.');
        }

        return new self($this->apiKey, $this->baseUrl, $this->transport, $trimmed);
    }

    /**
     * @param array{
     *     query?: array<string, mixed>|null,
     *     body?: array<string, mixed>|null,
     *     form?: array{filename: string, contents: string, contentType: string}|null,
     *     talentId?: string|null,
     *     clientId?: string|null
     * } $input
     */
    public function request(string $method, string $path, array $input = []): mixed
    {
        $headers = [
            'Accept' => 'application/json',
            'X-Klockwork-Key' => $this->apiKey,
            'User-Agent' => 'klockwork-php/' . Version::VERSION,
        ];

        $this->setHeaderIfNonEmptyString($headers, 'X-Klockwork-Customer', $this->customerId);
        $this->setHeaderIfNonEmptyString($headers, 'X-Klockwork-Talent', $input['talentId'] ?? null);
        $this->setHeaderIfNonEmptyString($headers, 'X-Klockwork-Client', $input['clientId'] ?? null);

        $body = null;
        if (isset($input['form'])) {
            $form = $input['form'];
            $body = [
                'file' => [
                    'filename' => $form['filename'],
                    'contents' => $form['contents'],
                    'contentType' => $form['contentType'],
                ],
            ];
        } elseif (array_key_exists('body', $input) && $input['body'] !== null) {
            $headers['Content-Type'] = 'application/json';
            $body = $this->encodeJson($input['body']);
        }

        $url = $this->baseUrl . $path . $this->queryString($input['query'] ?? null);
        $response = ($this->transport)($method, $url, $headers, $body);
        if (!$response instanceof TransportResponse) {
            throw new \RuntimeException('Klockwork transport must return a TransportResponse.');
        }

        $parsed = null;
        if ($response->body !== '') {
            try {
                $parsed = json_decode($response->body, true, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException) {
                $parsed = $response->body;
            }
        }

        if ($response->status < 200 || $response->status >= 300) {
            $details = is_array($parsed) && array_key_exists('details', $parsed) ? $parsed['details'] : null;
            throw new KlockworkError(
                $response->status,
                $this->errorMessage($response->status, $parsed, $response->statusText),
                $parsed,
                $details,
            );
        }

        if ($response->body !== '' && is_string($parsed)) {
            throw new KlockworkError($response->status, 'Invalid JSON response', $parsed);
        }

        return $parsed;
    }

    /**
     * @param array<string, string> $headers
     */
    private function setHeaderIfNonEmptyString(array &$headers, string $name, mixed $value): void
    {
        if (is_string($value) && $value !== '') {
            $headers[$name] = $value;
        }
    }

    /**
     * @param array<string, mixed> $body
     */
    private function encodeJson(array $body): string
    {
        $payload = $body === [] ? new \stdClass() : $body;

        return json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }

    /**
     * @param array<string, mixed>|null $query
     */
    private function queryString(?array $query): string
    {
        if ($query === null || $query === []) {
            return '';
        }

        $parts = [];
        foreach ($query as $key => $value) {
            if ($value === null) {
                continue;
            }
            if (is_bool($value)) {
                $rendered = $value ? 'true' : 'false';
            } elseif (is_string($value) || is_int($value) || is_float($value)) {
                $rendered = (string) $value;
            } else {
                throw new \InvalidArgumentException('Query values must be scalar.');
            }
            $parts[] = rawurlencode((string) $key) . '=' . rawurlencode($rendered);
        }

        return $parts === [] ? '' : '?' . implode('&', $parts);
    }

    private function errorMessage(int $status, mixed $body, string $statusText): string
    {
        if (is_array($body) && isset($body['error']) && is_string($body['error']) && $body['error'] !== '') {
            return $body['error'];
        }
        if (is_string($body) && $body !== '') {
            return $body;
        }

        return $statusText !== '' ? $statusText : 'Request failed with status ' . $status;
    }
}
