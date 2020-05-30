<?php namespace NumenCode\Fundamentals\Traits;

use NumenCode\Fundamentals\Scopes\PublishableScope;

trait Publishable
{
    public static function bootPublishable()
    {
        static::addGlobalScope(new PublishableScope);
    }

    public function isPublished()
    {
        return PublishableScope::isForced() ? true : $this->is_published;
    }
}
