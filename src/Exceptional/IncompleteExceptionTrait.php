<?php

/**
 * Exceptional
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Exceptional;

use ReflectionFunctionAbstract;

/**
 * @phpstan-require-implements IncompleteException
 */
trait IncompleteExceptionTrait
{
    public ?ReflectionFunctionAbstract $reflection {
        get => $this->stackFrame?->function->reflection;
    }
}
