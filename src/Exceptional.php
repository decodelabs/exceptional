<?php

/**
 * @package Exceptional
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs;

use BadMethodCallException;
use DecodeLabs\Exceptional\Exception;
use DecodeLabs\Exceptional\Factory;

final class Exceptional
{
    /**
     * Protected constructor inhibits instantiation
     */
    private function __construct()
    {
    }

    /**
     * Description
     *
     * @param array<mixed> $args
     */
    public static function __callStatic(string $type, array $args): Exception
    {
        $type = trim($type);

        if (!preg_match('|[.\\\\/]|', $type) && !preg_match('/^[A-Z]/', $type)) {
            throw new BadMethodCallException(
                'Method ' . $type . ' is not available in Exceptional'
            );
        }

        if (isset($args[0]) && is_array($args[0])) {
            array_unshift($args, null);
        }

        return Factory::create(
            explode(',', $type),
            1,
            /* @phpstan-ignore-next-line */
            ...$args
        );
    }
}
