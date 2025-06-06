<?php

/**
 * @package Exceptional
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Exceptional;

use DecodeLabs\Remnant\PreparedTraceException;
use Throwable;

interface Exception extends
    Throwable,
    PreparedTraceException
{
    public ?int $http { get; set; }
    public mixed $data { get; set; }
}
