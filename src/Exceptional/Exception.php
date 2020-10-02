<?php
/**
 * This file is part of the Exceptional package
 * @license http://opensource.org/licenses/MIT
 */
declare(strict_types=1);
namespace DecodeLabs\Exceptional;

use DecodeLabs\Glitch\Stack\Frame;
use DecodeLabs\Glitch\Stack\Trace;

use Throwable;

interface Exception extends Throwable
{
    public function setData($data);
    public function getData();

    public function setHttpStatus(?int $code);
    public function getHttpStatus(): ?int;

    public function getStackFrame(): Frame;
    public function getStackTrace(): Trace;
}
