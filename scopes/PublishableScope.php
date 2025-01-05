<?php namespace NumenCode\Fundamentals\Scopes;

use BackendAuth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Database\Eloquent\Builder;

class PublishableScope implements Scope
{
    public function apply(Builder $builder, Model $model)
    {
        if (!BackendAuth::check() && !app()->runningInConsole() | app()->runningUnitTests()) {
            $builder->where($model->getTable() . '.is_published', true);
        }
    }

    public function extend(Builder $builder)
    {
        $builder->macro('withUnpublished', function ($builder) {
            return $builder->withoutGlobalScope(static::class);
        });
    }
}
