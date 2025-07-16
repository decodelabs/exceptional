<?php

/**
 * @package Exceptional
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Exceptional\Tests;

use DecodeLabs\Exceptional\Exception;
use DecodeLabs\Exceptional\ExceptionTrait;
use DecodeLabs\Exceptional\IncompleteException;
use DecodeLabs\Exceptional\IncompleteExceptionTrait;
use Exception as RootException;

class TestIncompleteExceptionTrait extends RootException implements Exception, IncompleteException
{
    use ExceptionTrait;
    use IncompleteExceptionTrait;
}
