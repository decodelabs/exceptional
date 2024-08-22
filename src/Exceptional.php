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
use Throwable;

final class Exceptional
{
    /**
     * Protected constructor inhibits instantiation
     */
    private function __construct()
    {
    }

    /**
     * Generic call signature for PHPStan
     *
     * @param array<string,mixed> $params
     * @param array<string> $interfaces
     * @param array<string> $traits
     */
    private static function _phpstan(
        ?string $message = null,
        ?array $params = [],
        mixed $data = null,
        ?Throwable $previous = null,
        ?int $code = null,
        ?string $file = null,
        ?int $line = null,
        ?int $http = null,
        ?string $namespace = null,
        ?array $interfaces = null,
        ?array $traits = null
    ): Exception {
        return Factory::create(
            ['Generic'],
            1,
            $message,
            $params,
            $data,
            $previous,
            $code,
            $file,
            $line,
            $http,
            $namespace,
            $interfaces,
            $traits
        );
    }

    /**
     * Description
     *
     * @param array<mixed> $args
     */
    public static function __callStatic(
        string $type,
        array $args
    ): Exception {
        $type = trim($type);

        if (
            !preg_match('|[.\\\\/]|', $type) &&
            !preg_match('/^[A-Z]/', $type)
        ) {
            throw new BadMethodCallException(
                'Method ' . $type . ' is not available in Exceptional'
            );
        }

        if (
            isset($args[0]) &&
            is_array($args[0])
        ) {
            array_unshift($args, null);
        }

        return Factory::create(
            explode(',', $type),
            1,
            ...$args
        );
    }
}
