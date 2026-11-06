<?php

declare(strict_types=1);

namespace NumenCode\Fundamentals\Extensions;

use Cache;
use Cms\Classes\Theme;
use Config;
use Detection\MobileDetect;
use Illuminate\Support\Collection;
use NumenCode\Fundamentals\Classes\ContentImageResizer;

/**
 * Twig filters and functions registered by the plugin.
 */
class TwigExtension
{
    /**
     * Cache key of the deploy token returned by asset_hash() without a path.
     */
    public const ASSET_HASH_CACHE_KEY = 'numencode.fundamentals.asset.hash';

    /**
     * @return array<string, callable>
     */
    public static function filters(): array
    {
        return [
            'resize_images' => [new ContentImageResizer(), 'makeResizedUrls'],
            'url_path'      => [static::class, 'urlPath'],
        ];
    }

    /**
     * @return array<string, callable>
     */
    public static function functions(): array
    {
        return [
            'asset_hash'     => [static::class, 'assetHash'],
            'class_basename' => [static::class, 'classBasename'],
            'collect'        => [static::class, 'collect'],
            'd'              => [static::class, 'dump'],
            'dd'             => [static::class, 'dumpAndDie'],
            'detect'         => [static::class, 'detect'],
            'device_type'    => [static::class, 'deviceType'],
        ];
    }

    /**
     * Returns the path part of a URL: {{ 'https://example.com/a/b?c=1'|url_path }} → /a/b
     */
    public static function urlPath(mixed $value): string
    {
        if (!is_string($value) || $value === '') {
            return '';
        }

        $path = parse_url($value, PHP_URL_PATH);

        return is_string($path) ? $path : '';
    }

    /**
     * Cache-busting token for assets.
     *
     * - With a path, returns a short hash of the file's modification time, so it changes whenever the file
     *   changes: {{ 'assets/css/app.css'|theme }}?v={{ asset_hash('assets/css/app.css') }}
     *   The path can be relative to the active theme, relative to the project root, or a URL of the file.
     * - Without a path (or when the file isn't found), returns a token that changes whenever the
     *   application cache is cleared (e.g. on deploy).
     */
    public static function assetHash(?string $path = null): string
    {
        if ($path !== null && $path !== '' && $file = static::resolveAssetPath($path)) {
            return substr(md5($file . '|' . filemtime($file)), 0, 10);
        }

        return (string) Cache::rememberForever(self::ASSET_HASH_CACHE_KEY, fn () => date('YmdHis'));
    }

    /**
     * Resolves an asset path to an existing file inside the project, or null.
     */
    protected static function resolveAssetPath(string $path): ?string
    {
        // Accept URLs (e.g. the output of the |theme filter) by taking their path
        if (preg_match('#^(https?:)?//#i', $path)) {
            $path = static::urlPath($path);
        }

        $path = ltrim(str_replace('\\', '/', $path), '/');
        $basePath = realpath(base_path());
        $candidates = [];

        if ($theme = Theme::getActiveTheme()) {
            $candidates[] = $theme->getPath() . '/' . $path;
        }

        $candidates[] = base_path($path);

        foreach ($candidates as $candidate) {
            $file = realpath($candidate);

            // Only files inside the project can be used
            if ($file !== false && is_file($file) && $basePath !== false && str_starts_with($file, $basePath . DIRECTORY_SEPARATOR)) {
                return $file;
            }
        }

        return null;
    }

    public static function classBasename(object|string $class): string
    {
        return class_basename($class);
    }

    public static function collect(mixed $items = []): Collection
    {
        return collect($items);
    }

    /**
     * {{ d(var) }} – dumps the values, only when app.debug is enabled.
     */
    public static function dump(mixed ...$values): void
    {
        if (Config::get('app.debug')) {
            dump(...$values);
        }
    }

    /**
     * {{ dd(var) }} – dumps the values and ends the request, only when app.debug is enabled.
     */
    public static function dumpAndDie(mixed ...$values): void
    {
        if (Config::get('app.debug')) {
            dd(...$values);
        }
    }

    /**
     * Returns the request's MobileDetect instance: {% if detect().isiOS() %}...{% endif %}
     */
    public static function detect(): MobileDetect
    {
        return app(MobileDetect::class);
    }

    /**
     * Returns "mobile", "tablet" or "desktop", based on the request's user agent.
     *
     * Beware of full-page caching and CDNs: the cached HTML is served to every device.
     */
    public static function deviceType(): string
    {
        $detect = static::detect();

        return match (true) {
            $detect->isTablet() => 'tablet',
            $detect->isMobile() => 'mobile',
            default => 'desktop',
        };
    }
}
