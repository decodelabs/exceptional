<?php

/**
 * @package Exceptional
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Exceptional\Tests;

use DecodeLabs\Exceptional;

throw Exceptional::Runtime('This is a test', [
    'http' => 404
], [
    'data' => 'This is data'
]);
