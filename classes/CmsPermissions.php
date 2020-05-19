<?php namespace NumenCode\Fundamentals\Classes;

use UnexpectedValueException;
use Backend\Facades\BackendAuth;

class CmsPermissions
{
    protected static $revokes = [
        'create'    => [],
        'update'    => [],
        'delete'    => [],
        'duplicate' => [],
    ];

    public static function canCreate($controller)
    {
        return static::can('create', $controller);
    }

    public static function canUpdate($controller)
    {
        return static::can('update', $controller);
    }

    public static function canDelete($controller)
    {
        return static::can('delete', $controller);
    }

    public static function revokeCreate($group, $controller)
    {
        static::revoke('create', $group, $controller);
    }

    public static function revokeUpdate($group, $controller)
    {
        static::revoke('update', $group, $controller);
    }

    public static function revokeDelete($group, $controller)
    {
        static::revoke('delete', $group, $controller);
    }

    protected static function can($action, $controller)
    {
        static::validateAction($action);

        $controller = is_string($controller) ? $controller : get_class($controller);
        $groups = BackendAuth::getUser() ? BackendAuth::getUser()->groups->lists('code') : [];

        foreach ($groups as $group) {
            if (!empty(static::$revokes[$action][$group][$controller])) {
                return false;
            }
        }

        return true;
    }

    protected static function revoke($action, $group, $controller)
    {
        static::validateAction($action);

        if (is_array($group)) {
            return array_map(function ($group) use ($action, $controller) {
                static::revoke($action, $group, $controller);
            }, $group);
        }

        if (!isset(static::$revokes[$action][$group])) {
            static::$revokes[$action][$group] = [];
        }

        static::$revokes[$action][$group][$controller] = true;
    }

    protected static function validateAction($action)
    {
        if (!in_array($action, array_keys(static::$revokes))) {
            throw new UnexpectedValueException('Unexpected revoke type encountered: ' . $action);
        }
    }
}
