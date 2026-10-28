<?php

declare(strict_types=1);

namespace Klockwork;

/**
 * @internal
 */
final class PartnerPayload
{
    /**
     * @return array<string, mixed>
     */
    public static function requireArray(mixed $payload, int $status, string $message): array
    {
        if (!is_array($payload)) {
            throw new KlockworkError($status, $message, $payload);
        }

        return $payload;
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public static function nestedArray(array $payload, string $key, int $status, string $message): array
    {
        $value = $payload[$key] ?? null;
        if (!is_array($value)) {
            throw new KlockworkError($status, $message, $payload);
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $payload
     * @return list<array<string, mixed>>
     */
    public static function listOfArrays(array $payload, string $key, int $status, string $message): array
    {
        $value = $payload[$key] ?? null;
        if (!is_array($value)) {
            throw new KlockworkError($status, $message, $payload);
        }

        $items = [];
        foreach ($value as $item) {
            if (!is_array($item)) {
                throw new KlockworkError($status, $message, $payload);
            }
            $items[] = $item;
        }

        return $items;
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function string(array $payload, string $key, int $status): string
    {
        $value = $payload[$key] ?? null;
        if (!is_string($value) || $value === '') {
            throw new KlockworkError($status, 'Invalid partner response: missing ' . $key, $payload);
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function nullableString(array $payload, string $key, int $status): ?string
    {
        if (!array_key_exists($key, $payload) || $payload[$key] === null) {
            return null;
        }

        $value = $payload[$key];
        if (!is_string($value)) {
            throw new KlockworkError($status, 'Invalid partner response: ' . $key . ' must be a string or null', $payload);
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function optionalNullableString(array $payload, string $key, int $status): ?string
    {
        if (!array_key_exists($key, $payload)) {
            return null;
        }

        return self::nullableString($payload, $key, $status);
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function bool(array $payload, string $key, int $status): bool
    {
        $value = $payload[$key] ?? null;
        if (!is_bool($value)) {
            throw new KlockworkError($status, 'Invalid partner response: missing ' . $key, $payload);
        }

        return $value;
    }
}
