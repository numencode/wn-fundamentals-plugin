<?php namespace NumenCode\Fundamentals\Traits;

use NumenCode\Fundamentals\Scopes\PublishScope;

trait Publishable
{
    public static function bootIsPublished()
    {
        static::addGlobalScope(new PublishScope);
    }

    public function isPublished()
    {
        return PublishScope::isForced() ? true : $this->is_published;
    }
}
