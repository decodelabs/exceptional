<?php

/**
 * @package Exceptional
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Exceptional;

use DecodeLabs\Wellspring;
use DecodeLabs\Wellspring\Priority;

class AutoLoader
{
    protected static bool $registered = false;
    protected static int $checkCount = 0;

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
            Wellspring::register([self::class, 'loadClass'], Priority::Low);
            self::$registered = true;
        }
    }

    /**
     * Unregister as autoloader
     */
    public static function unregister(): void
    {
        if (self::$registered) {
            Wellspring::unregister([self::class, 'loadClass']);
            self::$registered = false;
        }
    }

    public static function loadClass(
        string $class
    ): void {
        if (
            !preg_match('/\\\\([a-zA-Z0-9_]*)Exception$/', $class) ||
            class_exists($class) ||
            interface_exists($class) ||
            trait_exists($class)
        ) {
            return;
        }

        Factory::create(
            types: [$class],
            rewind: 1,
            message: 'AutoLoader'
        );
    }
}
