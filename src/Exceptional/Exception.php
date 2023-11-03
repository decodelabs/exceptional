<?php

/**
 * @package Exceptional
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Exceptional;

use DecodeLabs\Glitch\Stack\PreparedTraceException;

use Throwable;

interface Exception extends
    Throwable,
    PreparedTraceException
{
    /**
     * @return $this
     */
    public function setData(
        mixed $data
    ): Exception;

    public function getData(): mixed;

    /**
     * @return $this
     */
    public function setHttpStatus(
        ?int $code
    ): Exception;

    public function getHttpStatus(): ?int;
}
