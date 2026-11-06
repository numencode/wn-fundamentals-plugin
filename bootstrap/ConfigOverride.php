<?php

declare(strict_types=1);

namespace NumenCode\Fundamentals\Bootstrap;

use Event;
use Winter\Storm\Support\Str;

/**
 * Changes the YAML configuration (fields, columns, ...) of other plugins and modules from code.
 *
 * Example – add a field to a model's fields.yaml:
 *
 *     ConfigOverride::extendFields(GalleryItem::class, function (array $config) {
 *         $config['fields']['caption'] = ['label' => 'Caption', 'type' => 'text'];
 *
 *         return $config;
 *     });
 *
 * The callback receives the parsed YAML as an array and returns the changed array
 * (or null to leave it unchanged).
 */
class ConfigOverride
{
    /**
     * Callbacks per public config file path.
     *
     * @var array<string, array<int, callable>>
     */
    protected static array $overrides = [];

    /**
     * Callbacks applied to every config file.
     *
     * @var array<int, callable>
     */
    protected static array $globalOverrides = [];

    public static function extendFields(string $class, callable $callback): void
    {
        static::extend('fields.yaml', $class, $callback);
    }

    public static function extendColumns(string $class, callable $callback): void
    {
        static::extend('columns.yaml', $class, $callback);
    }

    public static function extendImportColumns(string $class, callable $callback): void
    {
        static::extend('columns_import.yaml', $class, $callback);
    }

    public static function extendExportColumns(string $class, callable $callback): void
    {
        static::extend('columns_export.yaml', $class, $callback);
    }

    public static function extendImportExport(string $class, callable $callback): void
    {
        static::extend('config_import_export.yaml', $class, $callback);
    }

    /**
     * Registers a callback for every config file. It receives the public file path and the config array.
     */
    public static function extendAll(callable $callback): void
    {
        static::$globalOverrides[] = $callback;
    }

    /**
     * Registers a callback for a config file that belongs to a class, e.g. ('fields.yaml', Post::class)
     * for plugins/winter/blog/models/post/fields.yaml.
     */
    public static function extend(string $file, string $class, callable $callback): void
    {
        static::$overrides[static::filePath($file, $class)][] = $callback;
    }

    /**
     * Returns the public path of a config file that belongs to a class, as used by the
     * system.extendConfigFile event (lowercase).
     */
    public static function filePath(string $file, string $class): string
    {
        $path = str_replace('\\', '/', strtolower(ltrim($class, '\\')) . '/' . $file);

        if (Str::startsWith($path, ['system/', 'backend/', 'cms/'])) {
            return '/modules/' . $path;
        }

        return '/plugins/' . $path;
    }

    /**
     * Removes all registered callbacks (used in tests).
     */
    public static function flush(): void
    {
        static::$overrides = [];
        static::$globalOverrides = [];
    }

    public function init(): void
    {
        Event::listen('system.extendConfigFile', function ($publicFile, $config) {
            // An empty YAML file is parsed as null
            if (!is_string($publicFile) || !is_array($config)) {
                return null;
            }

            return static::apply($publicFile, $config);
        });
    }

    /**
     * Applies the registered callbacks to a config array. Returns the changed config, or null when nothing changed.
     *
     * @param array<string, mixed> $config
     * @return array<string, mixed>|null
     */
    public static function apply(string $publicFile, array $config): ?array
    {
        $changed = false;

        foreach (static::$overrides[strtolower($publicFile)] ?? [] as $callback) {
            if (is_array($result = $callback($config))) {
                $config = $result;
                $changed = true;
            }
        }

        foreach (static::$globalOverrides as $callback) {
            if (is_array($result = $callback($publicFile, $config))) {
                $config = $result;
                $changed = true;
            }
        }

        return $changed ? $config : null;
    }
}
