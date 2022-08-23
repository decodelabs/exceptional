<?php

/**
 * @package Exceptional
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Exceptional;

class AutoLoader
{
    /**
     * @var bool
     */
    protected static $registered = false;

    /**
     * Is registered as autoLoader
     */
    public static function isRegistered(): bool
    {
        return self::$registered;
    }

    /**
     * Register as autoloader
     */
    public static function register(): void
    {
        if (!self::$registered) {
            \spl_autoload_register([self::class, 'loadClass']);
            self::$registered = true;
        }
    }

    /**
     * Unregister as autoloader
     */
    public static function unregister(): void
    {
        if (self::$registered) {
            \spl_autoload_unregister([self::class, 'loadClass']);
            self::$registered = false;
        }
    }

    public static function loadClass(string $class): void
    {
        if (
            !preg_match('/\\\\([a-zA-Z0-9_]*)Exception$/', $class) ||
            class_exists($class)
        ) {
            return;
        }

        Factory::create([$class], 1, 'AutoLoader');
    }
}
