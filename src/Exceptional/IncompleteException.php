<?php

/**
 * Exceptional
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Exceptional;

use ReflectionFunctionAbstract;

interface IncompleteException extends Exception
{
    public ?ReflectionFunctionAbstract $reflection { get; }
}
