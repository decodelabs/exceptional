<?php

/**
 * @package Exceptional
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Exceptional;

use DecodeLabs\Glitch\Stack\Frame;
use DecodeLabs\Glitch\Stack\PreparedTraceException;
use DecodeLabs\Glitch\Stack\Trace;
use Throwable;

interface Exception extends
    Throwable,
    PreparedTraceException
{
    public ?int $http { get; set; }
    public mixed $data { get; set; }
}
