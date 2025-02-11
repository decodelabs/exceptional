<?php

/**
 * @package Exceptional
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Exceptional;

use ReflectionFunctionAbstract;

/**
 * @phpstan-require-implements IncompleteException
 */
trait IncompleteExceptionTrait
{
    /**
     * Get Reflection object for active function in stack frame
     */
    public ?ReflectionFunctionAbstract $reflection {
        get => $this->stackFrame?->reflection;
    }
}
