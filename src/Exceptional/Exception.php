<?php

/**
 * @package Exceptional
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Exceptional;

use DecodeLabs\Glitch\Stack\PreparedTraceException;

use Throwable;

interface Exception extends Throwable, PreparedTraceException
{
    public function setData($data);
    public function getData();

    public function setHttpStatus(?int $code);
    public function getHttpStatus(): ?int;
}
