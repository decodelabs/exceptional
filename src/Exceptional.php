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
use DecodeLabs\Remnant\Trace;
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
     * @param list<string> $interfaces
     * @param list<string> $traits
     */
    protected static function _phpstan(
        ?string $message = null,
        ?int $http = null,
        ?int $code = null,
        ?int $severity = null,
        mixed $data = null,
        ?string $file = null,
        ?int $line = null,
        ?Trace $stackTrace = null,
        ?Throwable $previous = null,
        ?string $namespace = null,
        ?array $interfaces = null,
        ?array $traits = null
    ): Exception {
        return Factory::create(
            types: ['Generic'],
            message: $message,
            http: $http,
            code: $code,
            severity: $severity,
            data: $data,
            rewind: 1,
            file: $file,
            line: $line,
            stackTrace: $stackTrace,
            previous: $previous,
            namespace: $namespace,
            interfaces: $interfaces,
            traits: $traits
        );
    }

    /**
     * Description
     *
     * @param array<int|string,mixed> $args
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

        $params = [];

        if (
            isset($args[0]) &&
            is_array($args[0])
        ) {
            unset($args[0]['types']);
            $params = array_merge($params, $args[0]);
            $args = [];
        }

        foreach ($args as $key => $arg) {
            $key = match ($key) {
                0 => 'message',
                1 => 'code',
                2 => 'http',
                3 => 'severity',
                4 => 'data',
                5 => 'file',
                6 => 'line',
                7 => 'stackTrace',
                8 => 'previous',
                9 => 'type',
                10 => 'namespace',
                11 => 'interfaces',
                12 => 'traits',
                default => $key
            };

            $params[$key] = $arg;
        }

        $params['types'] = explode(',', $type);
        $params['rewind'] ??= 1;

        // @phpstan-ignore-next-line
        return Factory::create(...$params);
    }
}
