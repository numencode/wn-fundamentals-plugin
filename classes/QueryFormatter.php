<?php

declare(strict_types=1);

namespace NumenCode\Fundamentals\Classes;

use DateTimeInterface;

/**
 * Formats SQL queries for debugging output.
 *
 * @internal Used by the dd_query() helper.
 */
class QueryFormatter
{
    /**
     * Replaces the "?" placeholders of an SQL query with its bindings. For debugging output only,
     * never for executing the query.
     *
     * @param array<int|string, mixed> $bindings
     */
    public static function interpolate(string $sql, array $bindings): string
    {
        $bindings = array_values($bindings);
        $index = 0;

        // A single pass, so "?" characters inside binding values are never replaced again
        return (string) preg_replace_callback('/\?/', function (array $match) use ($bindings, &$index): string {
            if (!array_key_exists($index, $bindings)) {
                return $match[0];
            }

            return static::formatValue($bindings[$index++]);
        }, $sql);
    }

    protected static function formatValue(mixed $value): string
    {
        return match (true) {
            $value === null => 'NULL',
            is_bool($value) => $value ? '1' : '0',
            is_int($value), is_float($value) => (string) $value,
            $value instanceof DateTimeInterface => "'" . $value->format('Y-m-d H:i:s') . "'",
            default => "'" . str_replace("'", "''", (string) $value) . "'",
        };
    }
}
