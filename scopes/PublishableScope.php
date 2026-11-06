<?php

declare(strict_types=1);

namespace NumenCode\Fundamentals\Scopes;

use Backend\Facades\BackendAuth;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Global scope of the Publishable trait.
 */
class PublishableScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if (static::shouldHideUnpublished()) {
            $builder->where($model->getQualifiedPublishedColumn(), true);
        }
    }

    /**
     * Unpublished records are hidden for visitors on the frontend. Backend users see everything,
     * and so do console commands (except during unit tests, so the scope can be tested).
     */
    public static function shouldHideUnpublished(): bool
    {
        if (app()->runningInConsole() && !app()->runningUnitTests()) {
            return false;
        }

        return !BackendAuth::check();
    }

    public function extend(Builder $builder): void
    {
        $builder->macro('withUnpublished', function (Builder $builder) {
            return $builder->withoutGlobalScope(static::class);
        });

        $builder->macro('onlyUnpublished', function (Builder $builder) {
            $model = $builder->getModel();

            return $builder->withoutGlobalScope(static::class)
                ->where($model->getQualifiedPublishedColumn(), false);
        });
    }
}
