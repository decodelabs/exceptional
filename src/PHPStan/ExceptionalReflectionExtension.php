<?php

/**
 * @package PHPStanDecodeLabs
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\PHPStan;

use DecodeLabs\Exceptional;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MethodReflection as MethodReflectionInterface;
use PHPStan\Reflection\MethodsClassReflectionExtension;

class ExceptionalReflectionExtension implements MethodsClassReflectionExtension
{
    public function hasMethod(
        ClassReflection $classReflection,
        string $methodName
    ): bool {
        if ($classReflection->getName() !== Exceptional::class) {
            return false;
        }

        return
            preg_match('|[.\\\\/]|', $methodName) ||
            preg_match('/^[A-Z]/', $methodName);
    }

    public function getMethod(
        ClassReflection $classReflection,
        string $methodName
    ): MethodReflectionInterface {
        $method = $classReflection->getNativeMethod('_phpstan');
        $output = new MethodReflection($classReflection, $methodName, $method->getVariants());
        $output->setStatic(true);

        return $output;
    }
}
