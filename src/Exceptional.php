<?php
/**
 * This file is part of the Exceptional package
 * @license http://opensource.org/licenses/MIT
 */
declare(strict_types=1);
namespace DecodeLabs;

use DecodeLabs\Exceptional\Exception;
use DecodeLabs\Exceptional\IncompleteException;
use DecodeLabs\Exceptional\Factory;

use DecodeLabs\Glitch\Stack\Frame;

use BadMethodCallException;

final class Exceptional
{
    /**
     * Protected constructor inhibits instantiation
     */
    protected function __construct()
    {
    }

    /**
     * Description
     */
    public static function __callStatic(string $type, array $args): Exception
    {
        $type = trim($type);

        if (!preg_match('|[.\\\\/]|', $type) && !preg_match('/^[A-Z]/', $type)) {
            throw new BadMethodCallException(
                'Method '.$type.' is not available in Exceptional'
            );
        }

        if (isset($args[0]) && is_array($args[0])) {
            array_unshift($args, null);
        }

        return Factory::create(
            explode(',', $type),
            1,
            ...$args
        );
    }


    /**
     * Shortcut to incomplete context method
     */
    public static function incomplete($data=null, int $rewind=0): void
    {
        $frame = Frame::create($rewind + 1);

        if ($frame->getVeneerFacade() !== null) {
            $frame = Frame::create($rewind + 2);
        }

        /** @phpstan-ignore-next-line */
        throw Factory::create(
            [],
            2 + $rewind,
            $frame->getSignature().' has not been implemented yet',
            [
                'interfaces' => [
                    IncompleteException::class
                ],
                'data' => $data
            ]
        );
    }
}
