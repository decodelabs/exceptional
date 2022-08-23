<?php

/**
 * @package Exceptional
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Exceptional;

use DecodeLabs\Coercion;

use DecodeLabs\Glitch\Proxy;
use DecodeLabs\Glitch\Stack\Frame;
use DecodeLabs\Glitch\Stack\Trace;

use ErrorException;
use Exception as RootException;
use Throwable;

trait ExceptionTrait
{
    protected ?int $http = null;
    protected mixed $data;
    protected int $rewind = 0;
    protected ?Trace $stackTrace = null;
    protected ?string $type = null;

    /**
     * @var array<string>
     */
    protected array $interfaces = [];

    /**
     * @var array<string, mixed>
     */
    protected array $params = [];

    /**
     * Override the standard Exception constructor to simplify instantiation
     *
     * @param array<string, mixed> $params
     */
    public function __construct(
        string $message,
        array $params = []
    ) {
        $args = [
            $message,
            Coercion::toInt($params['code'] ?? 0)
        ];

        if ($this instanceof ErrorException) {
            $args[] = Coercion::toInt($params['severity'] ?? 0);
            $args[] = Coercion::toString($params['file'] ?? '');
            $args[] = Coercion::toInt($params['line'] ?? 0);
        }

        $args[] = Coercion::toTypeOrNull(
            $params['previous'] ?? null,
            Throwable::class
        );

        /* @phpstan-ignore-next-line */
        parent::__construct(...$args);

        $this->file = Coercion::toString($params['file'] ?? '');
        $this->line = Coercion::toInt($params['line'] ?? 0);

        unset($params['code'], $params['previous'], $params['file'], $params['line']);

        $this->data = $params['data'] ?? null;
        $this->rewind = Coercion::toInt($params['rewind'] ?? 0);
        $this->http = Coercion::toIntOrNull($params['http'] ?? null);

        $this->type = Coercion::toStringOrNull($params['type'] ?? null);
        /* @phpstan-ignore-next-line */
        $this->interfaces = Coercion::toArray($params['interfaces'] ?? []);

        if (isset($params['stackTrace']) && $params['stackTrace'] instanceof Trace) {
            $this->stackTrace = $params['stackTrace'];
        }

        unset($params['data'], $params['rewind'], $params['http'], $params['type'], $params['interfaces'], $params['stackTrace']);
        $this->params = $params;
    }

    /**
     * Set arbitrary data
     */
    public function setData(mixed $data): Exception
    {
        $this->data = $data;
        return $this;
    }

    /**
     * Retrieve previously stored data
     */
    public function getData(): mixed
    {
        return $this->data;
    }


    /**
     * Associate error with HTTP status code
     */
    public function setHttpStatus(?int $code): Exception
    {
        $this->http = $code;
        return $this;
    }

    /**
     * Get associated HTTP status code
     */
    public function getHttpStatus(): ?int
    {
        return $this->http;
    }


    /**
     * Get first call from trace
     */
    public function getStackFrame(): Frame
    {
        $output = $this->getStackTrace()->getFirstFrame();

        if ($output === null) {
            throw new RootException('No stack frame to return');
        }

        return $output;
    }

    /**
     * Generate a StackTrace object from Exception trace
     */
    public function getStackTrace(): Trace
    {
        if (!$this->stackTrace) {
            $this->stackTrace = Trace::fromArray($this->getTrace(), $this->rewind);
        }

        return $this->stackTrace;
    }

    /**
     * Convert to string
     */
    public function __toString(): string
    {
        return $this->getMessage() . "\n" .
            'in ' . Proxy::normalizePath($this->getFile()) . ' : ' . $this->getLine() . "\n\n" .
            $this->getStackTrace();
    }


    /**
     * Export for dump inspection
     *
     * @return iterable<string, mixed>
     */
    public function glitchDump(): iterable
    {
        $parts = [];

        if (!empty($this->interfaces)) {
            $parts = $this->interfaces;
        }

        if (isset($this->type) && $this->type !== 'Exception') {
            $parts[] = $this->type;
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
        yield 'property:*http' => $this->http;

        foreach ($this->params as $key => $value) {
            yield 'property:*' . $key => $value;
        }

        yield '^property:!previous' => $this->getPrevious();
        yield 'values' => $this->data !== null ? ['data' => $this->data] : null;
        yield 'showKeys' => false;
        yield 'file' => $this->file;
        yield 'startLine' => $this->line;
        yield 'stackTrace' => $this->getStackTrace();


        // Severity
        if (null !== (
            $severity = Coercion::toIntOrNull($this->params['severity'] ?? null)
        )) {
            $defs = [];
            $constants = [
                'E_ERROR', 'E_WARNING', 'E_PARSE', 'E_NOTICE',
                'E_CORE_ERROR', 'E_CORE_WARNING', 'E_COMPILE_ERROR',
                'E_COMPILE_WARNING', 'E_USER_ERROR', 'E_USER_WARNING',
                'E_USER_NOTICE', 'E_STRICT', 'E_RECOVERABLE_ERROR',
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
