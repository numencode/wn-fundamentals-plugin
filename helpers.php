<?php

declare(strict_types=1);

use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use NumenCode\Fundamentals\Classes\QueryFormatter;
use System\Classes\PluginManager;

if (!function_exists('plugin_exists')) {
    /**
     * Checks whether a plugin is installed and enabled, e.g. plugin_exists('Winter.Translate').
     */
    function plugin_exists(string $plugin): bool
    {
        return PluginManager::instance()->exists($plugin);
    }
}

if (!function_exists('extend_class')) {
    /**
     * Extends an extendable class (model, controller, component...) with a behavior.
     *
     * Example: extend_class(\Winter\User\Models\User::class, \Acme\Demo\Behaviors\Sluggable::class);
     */
    function extend_class(string $class, string $behavior): void
    {
        $class::extend(function ($object) use ($behavior) {
            $object->extendClassWith($behavior);
        });
    }
}

if (!function_exists('array_insert')) {
    /**
     * Inserts the elements of $data into $haystack before the given position or key.
     *
     * - An integer $before is a position (0 = at the beginning).
     * - A string $before is a key; when the key doesn't exist, $data is appended at the end.
     *
     * Numeric keys are renumbered, string keys are preserved.
     *
     * @param array<array-key, mixed> $haystack
     * @param array<array-key, mixed> $data
     * @return array<array-key, mixed>
     */
    function array_insert(array $haystack, int|string $before, array $data): array
    {
        if (is_int($before)) {
            $position = $before;
        } else {
            $position = array_search($before, array_keys($haystack), true);

            if ($position === false) {
                $position = count($haystack);
            }
        }

        return array_merge(
            array_slice($haystack, 0, $position, true),
            $data,
            array_slice($haystack, $position, null, true)
        );
    }
}

if (!function_exists('array_before')) {
    /**
     * Moves the element with key $move so that it comes directly before the element with key $needle.
     * Returns the array unchanged when one of the keys doesn't exist.
     *
     * @param array<array-key, mixed> $haystack
     * @return array<array-key, mixed>
     */
    function array_before(array $haystack, int|string $needle, int|string $move): array
    {
        if ((string) $needle === (string) $move || !array_key_exists($needle, $haystack) || !array_key_exists($move, $haystack)) {
            return $haystack;
        }

        $result = [];

        foreach ($haystack as $key => $value) {
            if ((string) $key === (string) $move) {
                continue;
            }

            if ((string) $key === (string) $needle) {
                $result[$move] = $haystack[$move];
            }

            $result[$key] = $value;
        }

        return $result;
    }
}

if (!function_exists('array_search_recursive')) {
    /**
     * Searches a multidimensional array for a value (strict comparison) and returns the path of keys
     * leading to it, e.g. ['fields', 'title', 'label']. Returns an empty array when nothing is found.
     *
     * @param array<array-key, mixed> $haystack
     * @param array<int, array-key> $keys Path prefix, used internally for recursion.
     * @return array<int, array-key>
     */
    function array_search_recursive(mixed $needle, array $haystack, array $keys = []): array
    {
        foreach ($haystack as $key => $value) {
            if ($value === $needle) {
                return [...$keys, $key];
            }

            if (is_array($value) && $found = array_search_recursive($needle, $value, [...$keys, $key])) {
                return $found;
            }
        }

        return [];
    }
}

if (!function_exists('dd_query')) {
    /**
     * Dumps the executed SQL queries (with bindings filled in) and dies after the $count-th query.
     */
    function dd_query(int $count = 1): void
    {
        $executed = 0;

        DB::listen(function (QueryExecuted $query) use ($count, &$executed) {
            $output = sprintf('(%s ms) %s', $query->time, QueryFormatter::interpolate($query->sql, $query->bindings));

            if (++$executed >= $count) {
                dd($output);
            }

            dump($output);
        });
    }
}

if (!function_exists('ddd')) {
    /**
     * Same as dd(), but responds with HTTP status 500, so the output is easy to spot in the browser's network tab
     * (e.g. when debugging AJAX requests).
     */
    function ddd(mixed ...$args): never
    {
        if (!headers_sent()) {
            http_response_code(500);
        }

        dd(...$args);
    }
}

if (!function_exists('ddt')) {
    /**
     * Prints a simple backtrace (file : line - function) and dies, or dumps it when $die is false.
     *
     * @param int $skip Number of the most recent frames to leave out.
     */
    function ddt(int $skip = 0, bool $die = true): void
    {
        $lines = [];

        foreach (array_slice(debug_backtrace(), $skip) as $frame) {
            $lines[] = sprintf(
                '%s : %s - %s',
                $frame['file'] ?? '[internal]',
                $frame['line'] ?? '',
                isset($frame['class']) ? $frame['class'] . $frame['type'] . $frame['function'] : $frame['function']
            );
        }

        $output = implode(PHP_EOL, $lines) . PHP_EOL;

        if ($die) {
            exit($output);
        }

        dump($output);
    }
}
