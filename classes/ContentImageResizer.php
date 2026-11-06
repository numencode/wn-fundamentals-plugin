<?php

declare(strict_types=1);

namespace NumenCode\Fundamentals\Classes;

use System\Classes\ImageResizer;
use System\Classes\MediaLibrary;

/**
 * Twig filter |resize_images: in HTML content (e.g. from a rich editor), replaces media library images
 * that have a pixel width with a resized copy made by Winter's core image resizer.
 *
 *     {{ page.content|resize_images|raw }}
 *
 * The width comes from the width attribute or the inline style (width: 300px). The aspect ratio is kept.
 * Images that are not in the media library, have no pixel width, or can't be resized are left unchanged.
 */
class ContentImageResizer
{
    /**
     * Images wider than this are left unchanged (protects against huge resize requests).
     */
    public const MAX_WIDTH = 4000;

    public function makeResizedUrls(mixed $content): string
    {
        if (!is_string($content) || $content === '' || stripos($content, '<img') === false) {
            return is_string($content) ? $content : '';
        }

        return (string) preg_replace_callback('/<img\b[^>]*>/i', fn (array $match) => $this->processTag($match[0]), $content);
    }

    protected function processTag(string $tag): string
    {
        $src = $this->getAttribute($tag, 'src');
        $width = $this->getWidth($tag);

        if ($src === null || $width === null || !$this->isMediaUrl($src)) {
            return $tag;
        }

        $resized = ImageResizer::filterGetUrl($src, $width, null);

        if ($resized === '' || $resized === $src) {
            return $tag;
        }

        return $this->setAttribute($tag, 'src', $resized);
    }

    /**
     * Returns the pixel width of an image tag (width attribute or inline style), or null.
     */
    protected function getWidth(string $tag): ?int
    {
        $width = null;
        $style = $this->getAttribute($tag, 'style');

        if ($style !== null && preg_match('/(?:^|;)\s*width\s*:\s*(\d+(?:\.\d+)?)px\b/i', $style, $match)) {
            $width = (int) round((float) $match[1]);
        } elseif (($attribute = $this->getAttribute($tag, 'width')) !== null && preg_match('/^\s*(\d+)\s*(px)?\s*$/i', $attribute, $match)) {
            $width = (int) $match[1];
        }

        return $width !== null && $width > 0 && $width <= static::MAX_WIDTH ? $width : null;
    }

    /**
     * Checks whether a URL points to the media library.
     */
    protected function isMediaUrl(string $url): bool
    {
        $mediaPath = parse_url(MediaLibrary::url(''), PHP_URL_PATH);
        $path = parse_url($url, PHP_URL_PATH);
        $host = parse_url($url, PHP_URL_HOST);

        if (!is_string($mediaPath) || $mediaPath === '' || !is_string($path)) {
            return false;
        }

        // External images are never resized
        if ($host !== null && $host !== parse_url(url('/'), PHP_URL_HOST) && $host !== parse_url(MediaLibrary::url(''), PHP_URL_HOST)) {
            return false;
        }

        return str_starts_with($path, rtrim($mediaPath, '/') . '/');
    }

    protected function getAttribute(string $tag, string $name): ?string
    {
        $pattern = '/\s' . preg_quote($name, '/') . '\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|([^\s>]+))/i';

        if (!preg_match($pattern, $tag, $match, PREG_UNMATCHED_AS_NULL)) {
            return null;
        }

        // Group 1: double quotes, group 2: single quotes, group 3: unquoted
        $value = $match[1] ?? $match[2] ?? $match[3] ?? '';

        return html_entity_decode($value, ENT_QUOTES | ENT_HTML5);
    }

    protected function setAttribute(string $tag, string $name, string $value): string
    {
        $pattern = '/(\s' . preg_quote($name, '/') . '\s*=\s*)(?:"[^"]*"|\'[^\']*\'|[^\s>]+)/i';

        return (string) preg_replace_callback(
            $pattern,
            fn (array $match) => $match[1] . '"' . htmlspecialchars($value, ENT_QUOTES | ENT_HTML5) . '"',
            $tag,
            1
        );
    }
}
