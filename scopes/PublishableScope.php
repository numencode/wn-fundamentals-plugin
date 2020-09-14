<?php namespace NumenCode\Fundamentals\Scopes;

use Backend\Facades\BackendAuth;
use Illuminate\Support\Facades\App;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Database\Eloquent\Builder;

class PublishableScope implements Scope
{
    public function apply(Builder $builder, Model $model)
    {
        if (!BackendAuth::check() && !App::runningInConsole()) {
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
