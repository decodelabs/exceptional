<?php

/**
 * @package Exceptional
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Exceptional\Tests;

use DecodeLabs\Exceptional;
use DecodeLabs\Exceptional\Exception;
use DecodeLabs\Exceptional\ExceptionTrait;
use Exception as RootException;

class TestExceptionTrait extends RootException implements Exception
{
    use ExceptionTrait;
}
