<?php

declare(strict_types=1);

namespace NumenCode\Fundamentals\Classes;

use Backend\Facades\BackendAuth;
use UnexpectedValueException;

/**
 * Revokes backend actions (create, update, delete, duplicate) on a controller for backend user groups.
 *
 * Example (in a plugin's boot()):
 *
 *     CmsPermissions::revokeDelete(['owners', 'publishers'], Promotions::class);
 *
 * and in the controller or its views:
 *
 *     if (CmsPermissions::canDelete($this)) { ... }
 *
 * A user is denied an action when any of their groups (by group code) has it revoked.
 */
class CmsPermissions
{
    public const ACTIONS = ['create', 'update', 'delete', 'duplicate'];

    /**
     * Revoked actions: action => group code => controller class => true.
     *
     * @var array<string, array<string, array<string, bool>>>
     */
    protected static array $revokes = [];

    public static function canCreate(object|string $controller): bool
    {
        return static::can('create', $controller);
    }

    public static function canUpdate(object|string $controller): bool
    {
        return static::can('update', $controller);
    }

    public static function canDelete(object|string $controller): bool
    {
        return static::can('delete', $controller);
    }

    public static function canDuplicate(object|string $controller): bool
    {
        return static::can('duplicate', $controller);
    }

    /**
     * @param string|array<int, string> $group Group code(s)
     */
    public static function revokeCreate(string|array $group, string $controller): void
    {
        static::revoke('create', $group, $controller);
    }

    /**
     * @param string|array<int, string> $group Group code(s)
     */
    public static function revokeUpdate(string|array $group, string $controller): void
    {
        static::revoke('update', $group, $controller);
    }

    /**
     * @param string|array<int, string> $group Group code(s)
     */
    public static function revokeDelete(string|array $group, string $controller): void
    {
        static::revoke('delete', $group, $controller);
    }

    /**
     * @param string|array<int, string> $group Group code(s)
     */
    public static function revokeDuplicate(string|array $group, string $controller): void
    {
        static::revoke('duplicate', $group, $controller);
    }

    /**
     * Checks whether the current backend user may perform an action on a controller.
     */
    public static function can(string $action, object|string $controller): bool
    {
        static::validateAction($action);

        $controller = static::normalizeController($controller);
        $user = BackendAuth::getUser();
        $groups = $user ? $user->groups->pluck('code')->all() : [];

        foreach ($groups as $group) {
            if (!empty(static::$revokes[$action][$group][$controller])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Revokes an action on a controller for one or more backend user groups.
     *
     * @param string|array<int, string> $group Group code(s)
     */
    public static function revoke(string $action, string|array $group, string $controller): void
    {
        static::validateAction($action);

        foreach ((array) $group as $code) {
            static::$revokes[$action][$code][static::normalizeController($controller)] = true;
        }
    }

    /**
     * Removes all revokes (used in tests).
     */
    public static function flush(): void
    {
        static::$revokes = [];
    }

    protected static function normalizeController(object|string $controller): string
    {
        return ltrim(is_string($controller) ? $controller : get_class($controller), '\\');
    }

    protected static function validateAction(string $action): void
    {
        if (!in_array($action, static::ACTIONS, true)) {
            throw new UnexpectedValueException(sprintf('Unexpected revoke type encountered: %s', $action));
        }
    }
}
