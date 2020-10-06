<?php

/**
 * @package Exceptional
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Exceptional;

use ReflectionFunctionAbstract;

interface IncompleteException extends Exception
{
    public function getReflection(): ?ReflectionFunctionAbstract;
}
