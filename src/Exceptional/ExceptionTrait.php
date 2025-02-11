<?php

/**
 * @package Exceptional
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Exceptional;

use DecodeLabs\Glitch\Proxy;
use DecodeLabs\Glitch\Stack\Frame;
use DecodeLabs\Glitch\Stack\Trace;
use ErrorException;
use Exception as RootException;
use Throwable;

/**
 * @phpstan-require-implements Exception
 * @phpstan-require-extends RootException
 */
trait ExceptionTrait
{
    protected Parameters $parameters;

    public ?int $http {
        get => $this->parameters->http;
        set => $this->parameters->http = $value;
    }

    public mixed $data {
        get => $this->parameters->data;
        set => $this->parameters->data = $value;
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

    /**
     * Convert to string
     */
    public function __toString(): string
    {
        return $this->getMessage() . "\n" .
            'in ' . Proxy::normalizePath($this->getFile()) . ' : ' . $this->getLine() . "\n\n" .
            $this->stackTrace;
    }


    /**
     * Export for dump inspection
     *
     * @return iterable<string, mixed>
     */
    public function glitchDump(): iterable
    {
        $parts = [];

        if (!empty($this->parameters->interfaces)) {
            $parts = $this->parameters->interfaces;
        }

        if (
            isset($this->parameters->type) &&
            $this->parameters->type !== 'Exception'
        ) {
            $parts[] = $this->parameters->type;
        }

        if (!empty($parts)) {
            foreach ($parts as $i => $part) {
                $inner = explode('\\', $part);
                $parts[$i] = array_pop($inner);

                if ($parts[$i] === 'Exception') {
                    unset($parts[$i]);
                }
            }

            $parts = array_unique($parts);
            yield 'name' => implode(' | ', $parts);
        }

        yield 'type' => 'exception';
        yield 'text' => $this->message;
        yield 'class' => '@Exceptional';
        yield 'property:*code' => $this->code;
        yield 'property:*http' => $this->parameters->http;

        yield '^property:!previous' => $this->getPrevious();
        yield 'values' => $this->parameters->data !== null ? ['data' => $this->parameters->data] : null;
        yield 'showKeys' => false;
        yield 'file' => $this->file;
        yield 'startLine' => $this->line;
        yield 'stackTrace' => $this->stackTrace;


        // Severity
        if (null !== (
            $severity = $this->parameters->severity
        )) {
            $defs = [];
            $constants = [
                'E_ERROR', 'E_WARNING', 'E_PARSE', 'E_NOTICE',
                'E_CORE_ERROR', 'E_CORE_WARNING', 'E_COMPILE_ERROR',
                'E_COMPILE_WARNING', 'E_USER_ERROR', 'E_USER_WARNING',
                'E_USER_NOTICE', 'E_RECOVERABLE_ERROR',
                'E_DEPRECATED', 'E_USER_DEPRECATED'
            ];

            foreach ($constants as $constant) {
                $value = constant($constant);

                if ($severity & $value) {
                    $defs[] = $constant;
                }
            }

            if (!empty($defs)) {
                yield 'definition' => implode(' | ', $defs);
            }
        }
    }
}
