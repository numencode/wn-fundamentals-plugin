<?php

declare(strict_types=1);

namespace NumenCode\Fundamentals\Traits;

use NumenCode\Fundamentals\Scopes\PublishableScope;

/**
 * Hides unpublished records on the frontend. Logged-in backend users still see them (so they can preview
 * content), and the backend and console are not affected.
 *
 * The model needs a boolean column, "is_published" by default. Use another column with:
 *
 *     const PUBLISHED_COLUMN = 'active';
 *
 * Query helpers: Model::withUnpublished() (all records) and Model::onlyUnpublished().
 */
trait Publishable
{
    public static function bootPublishable(): void
    {
        static::addGlobalScope(new PublishableScope());
    }

    public function isPublished(): bool
    {
        return (bool) $this->{$this->getPublishedColumn()};
    }

    public function getPublishedColumn(): string
    {
        return defined(static::class . '::PUBLISHED_COLUMN') ? static::PUBLISHED_COLUMN : 'is_published';
    }

    public function getQualifiedPublishedColumn(): string
    {
        return $this->qualifyColumn($this->getPublishedColumn());
    }
}
