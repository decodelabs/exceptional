<?php

/**
 * Exceptional
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Exceptional\Tests;

use DecodeLabs\Exceptional;

throw Exceptional::Runtime(
    message: 'This is a test',
    http: 404,
    data: [
        'data' => 'This is data'
    ]
);
