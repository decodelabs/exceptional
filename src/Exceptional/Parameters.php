<?php

/**
 * @package Exceptional
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Exceptional;

use DecodeLabs\Remnant\Trace;
use Stringable;
use Throwable;

class Parameters
{
    public ?string $message = null;
    public ?int $code = null;
    public ?int $http = null;
    public ?int $severity = null;

    public mixed $data = null;

    public int $rewind = 0 {
        set(?int $value) {
            $this->rewind = max($value ?? 0, 0);
        }
    }

    public ?string $file = null;
    public ?int $line = null;
    public ?Trace $stackTrace = null;
    public ?Throwable $previous = null;

    public ?string $type = null;
    public ?string $namespace = null;

    /**
     * @var list<string>
     */
    public array $interfaces = [] {
        set(?array $value) {
            $this->interfaces = array_values(array_unique($value ?? []));
        }
    }

    /**
     * @var list<string>
     */
    public array $traits = [] {
        set(?array $value) {
            $this->traits = array_values(array_unique($value ?? []));
        }
    }

    /**
     * @param list<string> $interfaces
     * @param list<string> $traits
     */
    public function __construct(
        string|Stringable|null $message = null,
        ?int $code = null,
        ?int $http = null,
        ?int $severity = null,
        mixed $data = null,
        ?int $rewind = 0,
        ?string $file = null,
        ?int $line = null,
        ?Trace $stackTrace = null,
        ?Throwable $previous = null,
        ?string $type = null,
        ?string $namespace = null,
        ?array $interfaces = [],
        ?array $traits = []
    ) {
        if($message !== null) {
            $this->message = (string)$message;
        }

        $this->code = $code;
        $this->http = $http;
        $this->severity = $severity;
        $this->data = $data;
        $this->file = $file;
        $this->line = $line;
        $this->rewind = $rewind;
        $this->stackTrace = $stackTrace;
        $this->previous = $previous;
        $this->type = $type;
        $this->namespace = $namespace;
        $this->interfaces = $interfaces;
        $this->traits = $traits;
    }
}
