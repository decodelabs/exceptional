<?php

/**
 * @package Exceptional
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Exceptional;

use DecodeLabs\Monarch;
use DecodeLabs\Remnant\Frame;
use DecodeLabs\Remnant\Trace;
use ErrorException;
use Exception as RootException;

/**
 * @phpstan-require-implements Exception
 * @phpstan-require-extends RootException
 */
trait ExceptionTrait
{
    public protected(set) Parameters $parameters;

    public ?int $http {
        get => $this->parameters->http;
        set { $this->parameters->http = $value; }
    }

    public mixed $data {
        get => $this->parameters->data;
        set { $this->parameters->data = $value; }
    }

    public Trace $stackTrace {
        get {
            if (!$this->parameters->stackTrace) {
                $this->parameters->stackTrace = Trace::fromArray(
                    $this->getTrace(),
                    $this->parameters->rewind
                );
            }

            return $this->parameters->stackTrace;
        }
    }

    public ?Frame $stackFrame {
        get => $this->stackTrace->getFirstFrame();
    }

    /**
     * Override the standard Exception constructor to simplify instantiation
     */
    public function __construct(
        ?Parameters $params = null
    ) {
        $params ??= new Parameters();
        $this->parameters = $params;

        if ($this instanceof ErrorException) {
            parent::__construct(
                message: $params->message ?? 'Unknown error',
                code: $params->code ?? 0,
                // @phpstan-ignore-next-line
                severity: $params->severity ?? 1,
                // @phpstan-ignore-next-line
                filename: $params->file,
                // @phpstan-ignore-next-line
                line: $params->line,
                previous: $params->previous,
            );
        } else {
            parent::__construct(
                message: $params->message ?? 'Unknown error',
                code: $params->code ?? 0,
                previous: $params->previous
            );
        }

        $this->file = $params->file ?? '';
        $this->line = $params->line ?? 0;
    }

    public function __toString(): string
    {
        $file = $this->getFile();

        // @phpstan-ignore-next-line
        if (class_exists(Monarch::class)) {
            // @phpstan-ignore-next-line
            $file = Monarch::getPaths()->prettify($file);
        }

        /** @var string $file */

        return $this->getMessage() . "\n" .
            'in ' . $file . ' : ' . $this->getLine() . "\n\n" .
            $this->stackTrace;
    }
}
