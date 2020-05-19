<?php namespace NumenCode\Fundamentals\Scopes;

use Backend\Facades\BackendAuth;
use Illuminate\Support\Facades\App;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Database\Eloquent\Builder;

class PublishScope implements Scope
{
    protected static $forcePublished = false;

    protected static $disablePreview = false;

    public static function forcePublished($toggle = true)
    {
        static::$forcePublished = $toggle;
    }

    public static function disablePreview($toggle = true)
    {
        static::$disablePreview = $toggle;
    }

    public static function isForced()
    {
        return static::$forcePublished;
    }

    public function apply(Builder $builder, Model $model)
    {
        if (static::$forcePublished) {
            return;
        }

        if (static::$disablePreview || (!BackendAuth::check() && !App::runningInConsole())) {
            $builder->where($model->getTable() . '.is_published', 1);
        }
    }

    public function extend(Builder $builder)
    {
        $builder->macro('withUnpublished', function ($builder) {
            return $builder->withoutGlobalScope(static::class);
        });
    }
}
