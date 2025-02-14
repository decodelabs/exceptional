<?php

// ecs.php

declare(strict_types=1);

use Symplify\EasyCodingStandard\Config\ECSConfig;
use PhpCsFixer\Fixer\ClassNotation\ProtectedToPrivateFixer;

return ECSConfig::configure()
    ->withPaths([
        __DIR__ . '/src',
        __DIR__ . '/tests'
    ])
    ->withPreparedSets(
        cleanCode: true,
        psr12: true
    )
    ->withSkip([
        ProtectedToPrivateFixer::class
    ]);
