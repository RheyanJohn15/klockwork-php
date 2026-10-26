<?php

declare(strict_types=1);

namespace Klockwork;

final class Subject
{
    public static function assertApiKey(string $apiKey): string
    {
        $trimmed = trim($apiKey);
        if (!str_starts_with($trimmed, 'kw_')) {
            throw new \InvalidArgumentException('Klockwork API key must start with kw_');
        }

        return $trimmed;
    }

    public static function requireTalent(?string $explicit, ?string $bound): string
    {
        $id = trim($explicit ?? $bound ?? '');
        if ($id === '') {
            throw new \InvalidArgumentException(
                'A talent id is required for Solo API calls. Pass talentId or call solo.forTalent().',
            );
        }

        return $id;
    }

    public static function requireClient(?string $explicit, ?string $bound): string
    {
        $id = trim($explicit ?? $bound ?? '');
        if ($id === '') {
            throw new \InvalidArgumentException(
                'A client id is required for company API calls. Pass clientId or call company.forClient().',
            );
        }

        return $id;
    }

    /**
     * @param array<string, mixed>|null $input
     * @return array{talentId: ?string, rest: array<string, mixed>}
     */
    public static function splitTalent(?array $input): array
    {
        $split = self::split($input, 'talentId');

        return ['talentId' => $split['id'], 'rest' => $split['rest']];
    }

    /**
     * @param array<string, mixed>|null $input
     * @return array{clientId: ?string, rest: array<string, mixed>}
     */
    public static function splitClient(?array $input): array
    {
        $split = self::split($input, 'clientId');

        return ['clientId' => $split['id'], 'rest' => $split['rest']];
    }

    public static function segment(string $id): string
    {
        return rawurlencode($id);
    }

    /**
     * @param array<string, mixed>|null $input
     * @return array{id: ?string, rest: array<string, mixed>}
     */
    private static function split(?array $input, string $key): array
    {
        if ($input === null) {
            return ['id' => null, 'rest' => []];
        }

        $id = $input[$key] ?? null;
        unset($input[$key]);
        if ($id !== null && !is_string($id)) {
            throw new \InvalidArgumentException($key . ' must be a string.');
        }

        return ['id' => $id, 'rest' => $input];
    }
}
