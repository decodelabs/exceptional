<?php

/**
 * @package Exceptional
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Exceptional;

use DecodeLabs\Coercion;
use DecodeLabs\Exceptional\Exception as ExceptionInterface;

use Exception as RootException;
use InvalidArgumentException;
use LogicException;
use Throwable;

/**
 * Automatically generate Exceptions on the fly based on scope and
 * requested interface types
 */
class Factory
{
    public const STANDARD = [
        'Logic' => [
            'type' => 'LogicException'
        ],
            'BadFunctionCall' => [
                'extend' => 'Logic',
                'type' => 'BadFunctionCallException'
            ],
                'BadMethodCall' => [
                    'extend' => 'BadFunctionCall',
                    'type' => 'BadMethodCallException'
                ],

            'Domain' => [
                'extend' => 'Logic',
                'type' => 'DomainException'
            ],
            'InvalidArgument' => [
                'extend' => 'Logic',
                'type' => 'InvalidArgumentException'
            ],
            'Length' => [
                'extend' => 'Logic',
                'type' => 'LengthException'
            ],
            'OutOfRange' => [
                'extend' => 'Logic',
                'type' => 'OutOfRangeException'
            ],

            'Definition' => [
                'extend' => 'Logic'
            ],
            'Implementation' => [
                'extend' => 'Logic'
            ],
                'NotImplemented' => [
                    'extend' => 'Implementation',
                    'http' => 501
                ],

            'Unsupported' => [
                'extend' => 'Logic'
            ],


        'Runtime' => [
            'type' => 'RuntimeException'
        ],
            'OutOfBounds' => [
                'extend' => 'Runtime',
                'type' => 'OutOfBoundsException'
            ],
            'Overflow' => [
                'extend' => 'Runtime',
                'type' => 'OverflowException'
            ],
            'Range' => [
                'extend' => 'Runtime',
                'type' => 'RangeException'
            ],
            'Underflow' => [
                'extend' => 'Runtime',
                'type' => 'UnderflowException'
            ],
            'UnexpectedValue' => [
                'extend' => 'Runtime',
                'type' => 'UnexpectedValueException'
            ],

            'Io' => [
                'extend' => 'Runtime'
            ],
                'Protocol' => [
                    'extend' => 'Io'
                ],

            'BadRequest' => [
                'extend' => 'Runtime',
                'http' => 400
            ],
            'Unauthorized' => [
                'extend' => 'Runtime',
                'http' => 401
            ],
                'Forbidden' => [
                    'extend' => 'Unauthorized',
                    'http' => 403
                ],
            'NotFound' => [
                'extend' => 'Runtime',
            ],
                'ResourceNotFound' => [
                    'extend' => 'NotFound',
                    'http' => 404
                ],

            'Setup' => [
                'extend' => 'Runtime'
            ],
                'ComponentUnavailable' => [
                    'extend' => 'Setup'
                ],
                'ServiceUnavailable' => [
                    'extend' => 'Setup',
                    'http' => 503
                ],

        'Error' => [
            'type' => 'ErrorException'
        ]
    ];


    public const REWIND = 2;

    /**
     * @var array<Exception>
     */
    private static array $instances = [];

    protected ?string $message = null;

    /**
     * @var array<string, mixed>
     */
    protected array $params = [];

    protected ?string $baseClass = null;
    protected ?string $namespace = null;

    /**
     * @var array<string, bool>
     */
    protected array $interfaces = [];

    /**
     * @var array<string, bool>
     */
    protected array $traits = [];


    /**
     * @var array<string, array<string>>
     */
    protected array $interfaceIndex = [];

    /**
     * @var array<string, string>
     */
    protected array $interfaceDefs = [];

    protected string $exceptionDef;
    protected bool $autoLoad = false;


    /**
     * Generate a context specific, message oriented throwable error
     *
     * @param array<string> $types
     * @param array<string, mixed> $params
     * @param array<string> $interfaces
     * @param array<string> $traits
     */
    public static function create(
        array $types,
        ?int $rewind = 0,
        ?string $message = null,
        ?array $params = [],
        mixed $data = null,
        ?Throwable $previous = null,
        ?int $code = null,
        ?string $file = null,
        ?int $line = null,
        ?int $http = null,
        ?string $namespace = null,
        ?array $interfaces = null,
        ?array $traits = null
    ): Exception {
        return (new self(
            $types,
            $rewind,
            $message,
            $params,
            $data,
            $previous,
            $code,
            $file,
            $line,
            $http,
            $namespace,
            $interfaces,
            $traits
        ))->build();
    }

    /**
     * Begin new factory process
     *
     * @param array<string> $types
     * @param array<string, mixed> $params
     * @param array<string> $interfaces
     * @param array<string> $traits
     */
    protected function __construct(
        array $types,
        ?int $rewind,
        ?string $message,
        ?array $params,
        mixed $data,
        ?Throwable $previous,
        ?int $code,
        ?string $file,
        ?int $line,
        ?int $http,
        ?string $namespace,
        ?array $interfaces,
        ?array $traits
    ) {
        // Turn off autoloading
        $this->autoLoad = AutoLoader::isRegistered();
        AutoLoader::unregister();

        // Message
        $this->message =
            $message ??
            Coercion::toStringOrNull($params['message'] ?? null) ??
            'Undefined error';


        // Params
        $this->params = $params ?? [];
        $this->params['data'] = $data ?? $params['data'] ?? null;
        $this->params['previous'] = $previous ?? $params['previous'] ?? null;
        $this->params['code'] = Coercion::toInt($code ?? $params['code'] ?? 0);
        $this->params['http'] = $http ?? $params['http'] ?? null;

        if (!$this->params['previous'] instanceof Throwable) {
            $this->params['previous'] = null;
        }

        // Trace
        $this->params['rewind'] = $rewind = (int)max(
            $rewind ?? Coercion::toIntOrNull($params['rewind'] ?? null) ?? 1,
            0
        );

        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, (int)($rewind + static::REWIND + 1));
        $key = $rewind + static::REWIND;
        $lastTrace = $trace[$key - 1];

        $this->params['file'] = $file ?? $params['file'] ?? $lastTrace['file'] ?? null;
        $this->params['line'] = $line ?? $params['line'] ?? $lastTrace['line'] ?? null;

        if (!is_string($this->params['file'])) {
            $this->params['file'] = null;
        }
        if (!is_int($this->params['line'])) {
            $this->params['line'] = null;
        }

        // Namespace
        $this->prepareTargetNamespace(
            Coercion::toStringOrNull($namespace ?? $params['namespace'] ?? null),
            $trace[$key] ?? null
        );

        // Inheritance
        $this->interfaceIndex['\\DecodeLabs\\Exceptional\\Exception'] = [];
        $this->interfaceIndex['\\DecodeLabs\\Glitch\\Dumpable'] = [];
        $this->traits['\\DecodeLabs\\Exceptional\\ExceptionTrait'] = true;

        $this->importTypes($types);
        $this->importInterfaces($interfaces ?? []);
        /* @phpstan-ignore-next-line */
        $this->importInterfaces(Coercion::toArray($this->params['interfaces'] ?? []));
        $this->importTraits($traits ?? []);
        /* @phpstan-ignore-next-line */
        $this->importTraits(Coercion::toArray($this->params['traits'] ?? []));


        // Cleanup
        unset(
            $this->params['message'],
            $this->params['namespace'],
            $this->params['interfaces'],
            $this->params['traits']
        );
    }


    /**
     * Prepare target namespace
     *
     * @param array<string, mixed>|null $frame
     */
    protected function prepareTargetNamespace(
        ?string $namespace,
        ?array $frame
    ): void {
        $this->namespace = $namespace;

        if (
            $this->namespace === null &&
            $frame !== null
        ) {
            $class = Coercion::toStringOrNull($frame['class'] ?? null);

            if (!empty($class)) {
                if (false !== strpos($class, 'class@anon')) {
                    $this->namespace = null;
                } else {
                    $parts = explode('\\', $class);
                    array_pop($parts);
                    $this->namespace = implode('\\', $parts);
                }
            }
        }

        if ($this->namespace !== null) {
            $this->namespace = ltrim($this->namespace, '\\');
        }

        if (empty($this->namespace)) {
            $this->namespace = null;
        } else {
            $this->namespace = '\\' . $this->namespace;
        }
    }


    /**
     * Import type definitions
     *
     * @param array<string> $types
     */
    protected function importTypes(array $types): void
    {
        foreach ($types as $type) {
            $type = trim($type);

            if (empty($type)) {
                continue;
            }

            if (!preg_match('/(.+)(Exception)(Trait)?$/', $type, $matches)) {
                $type .= 'Exception';
            }

            $isTraitName = isset($matches[3]);

            if (false !== strpos($type, '/')) {
                // Path style
                $origType = $type;
                $type = str_replace('/', '\\', $type);

                if (substr($type, 0, 1) == '.') {
                    if ($this->namespace === null) {
                        throw new LogicException(
                            'Stack context is not within a namespace for path dereferencing: ' . $origType
                        );
                    }

                    $type = $this->namespace . '\\' . $type;
                }

                $type = '\\' . ltrim($type, '\\');

                if (false !== strpos($type, '.')) {
                    $parts = [];

                    foreach (explode('\\', $type) as $part) {
                        if ($part == '.') {
                            continue;
                        } elseif ($part == '..') {
                            array_pop($parts);
                        } else {
                            $parts[] = $part;
                        }
                    }

                    $type = implode('\\', $parts);
                }
            } else {
                // Namespace style
                if (false === strpos($type, '\\')) {
                    $type = $this->namespace . '\\' . $type;
                }
            }


            // Named trait
            if ($isTraitName) {
                if (!trait_exists($type)) {
                    throw new InvalidArgumentException(
                        'Trait not found: ' . $type
                    );
                }

                $this->traits[$type] = true;
                continue;
            }

            // Named class
            if (
                class_exists($type) &&
                is_a($type, RootException::class, true)
            ) {
                if ($this->baseClass !== null) {
                    throw new InvalidArgumentException(
                        'Exception has already defined base type: ' . $this->baseClass
                    );
                }

                $this->baseClass = trim($type, '\\');
            }

            // Ensure root slash
            if (substr($type, 0, 1) !== '\\') {
                $type = '\\' . $type;
            }

            $this->interfaces[$type] = true;
        }
    }


    /**
     * Import interface definitions
     *
     * @param array<string> $interfaces
     */
    protected function importInterfaces(array $interfaces): void
    {
        foreach ($interfaces as $interface) {
            if (substr($interface, 0, 1) !== '\\') {
                $interface = '\\' . $interface;
            }

            if (!interface_exists($interface)) {
                throw new InvalidArgumentException(
                    $interface . ' is not an interface'
                );
            }

            $this->interfaces[$interface] = true;
        }
    }

    /**
     * Import trait definitions
     *
     * @param array<string> $traits
     */
    protected function importTraits(array $traits): void
    {
        foreach ($traits as $trait) {
            if (substr($trait, 0, 1) !== '\\') {
                $trait = '\\' . $trait;
            }

            if (!trait_exists($trait)) {
                throw new InvalidArgumentException(
                    $trait . ' is not an trait'
                );
            }

            $this->traits[$trait] = true;
        }
    }




    /**
     * Build exception
     */
    public function build(): Exception
    {
        // Named interfaces
        foreach ($this->interfaces as $interface => $enabled) {
            if ($enabled) {
                $this->indexInterface($interface);
            }
        }

        // Definitions
        $interfaces = $this->buildDefinitions();
        $hash = $this->compileDefinitions();

        // Params
        $params = $this->params;
        $params['type'] = $this->baseClass;
        $params['interfaces'] = $interfaces;

        // Reenable AutoLoader
        if ($this->autoLoad) {
            AutoLoader::register();
        }

        // Instantiate
        return new self::$instances[$hash]($this->message, $params);
    }

    /**
     * Add interface info to class extend list
     */
    protected function indexInterface(string $interface): void
    {
        $parts = explode('\\', ltrim($interface, '\\'));
        $name = substr((string)array_pop($parts), 0, -9);

        // Trait
        $traitName = $interface . 'Trait';

        if (trait_exists($traitName)) {
            $this->traits[$traitName] = true;
        }

        // Namespace
        $namespaceParent = null;

        if (!empty($parts)) {
            $namespaceParent = $this->indexNamespaceInterfaces($parts);
        }


        // Interface
        if (
            ($classExists = class_exists($interface)) &&
            is_a($interface, RootException::class, true)
        ) {
            $baseClass = trim($interface, '\\');

            if (
                $this->baseClass !== null &&
                $this->baseClass !== $baseClass
            ) {
                throw new InvalidArgumentException(
                    'Exception has already defined base type: ' . $this->baseClass
                );
            }

            $this->baseClass = $baseClass;
        }


        // Package
        if (isset(static::STANDARD[$name])) {
            $this->indexPackageInterface($name);

            // Interface
            if (
                !$classExists &&
                !isset($this->interfaceIndex[$interface])
            ) {
                $this->interfaceIndex[$interface] = [
                    '\\DecodeLabs\\Exceptional\\' . $name . 'Exception'
                ];

                if ($namespaceParent !== null) {
                    $this->interfaceIndex[$interface][] = $namespaceParent;
                }
            }
        } else {
            // User
            if (
                !$classExists &&
                !isset($this->interfaceIndex[$interface])
            ) {
                $this->interfaceIndex[$interface] = ['\\' . ExceptionInterface::class];
            }
        }
    }

    /**
     * Index namespace interface
     *
     * @param array<string> $parts
     */
    protected function indexNamespaceInterfaces(array $parts): ?string
    {
        $set = [];
        $first = $last = '\\DecodeLabs\\Exceptional\\Exception';

        while (!empty($parts)) {
            $set[] = array_shift($parts);
            $interface = '\\' . implode('\\', $set) . '\\Exception';

            // Check
            if (class_exists($interface)) {
                // We have to skip
                continue;
            }

            if (
                isset($this->interfaceIndex[$interface]) ||
                interface_exists($interface)
            ) {
                $last = $interface;
                continue;
            }

            $this->interfaceIndex[$interface] = [$last];
            $last = $interface;
        }

        if ($last !== $first) {
            return $last;
        } else {
            return null;
        }
    }


    /**
     * Index package interface
     */
    protected function indexPackageInterface(string $name): void
    {
        $standard = static::STANDARD[$name];
        $prefix = '\\DecodeLabs\\Exceptional\\';
        $interface = $prefix . $name . 'Exception';

        // Check
        $classExists = class_exists($interface);

        // Base class
        if (
            isset($standard['type']) &&
            $this->baseClass === null
        ) {
            $this->baseClass = $standard['type'];
        }

        // Http
        if (
            isset($standard['http']) &&
            !isset($this->params['http'])
        ) {
            $this->params['http'] = $standard['http'];
        }


        // Interface
        if (isset($standard['extend'])) {
            $this->indexPackageInterface($standard['extend']);

            if (!$classExists) {
                $this->interfaceIndex[$interface] = [$prefix . $standard['extend'] . 'Exception'];
            }
        } else {
            if (!$classExists) {
                $this->interfaceIndex[$interface] = [$prefix . 'Exception'];
            }
        }
    }






    /**
     * Build interface definitions
     *
     * @return array<string>
     */
    protected function buildDefinitions(): array
    {
        // Ensure base class
        if ($this->baseClass === null) {
            $this->baseClass = RootException::class;
        }

        // Create definitions for needed interfaces
        foreach ($this->interfaceIndex as $interface => $extends) {
            if (!empty($extends)) {
                $this->defineInterface($interface, $extends);
            }
        }


        // Build class def
        $this->exceptionDef = 'return new class(\'\') extends ' . $this->baseClass;
        $interfaceMap = $this->interfaceIndex;

        foreach ($this->interfaceIndex as $interface => $extends) {
            foreach ($extends as $extend) {
                unset($interfaceMap[$extend]);
            }
        }

        if (empty($interfaceMap)) {
            $interfaces = [ExceptionInterface::class];
        } else {
            $interfaces = array_keys($interfaceMap);
        }

        $this->exceptionDef .= ' implements ' . implode(',', $interfaces);
        $this->exceptionDef .= ' {';

        foreach ($this->traits as $trait => $enabled) {
            if ($enabled) {
                $this->exceptionDef .= 'use ' . $trait . ';';
            }
        }

        $this->exceptionDef .= '};';

        // Output interfaces
        foreach ($interfaces as $i => $interface) {
            if (empty($this->interfaceIndex[$interface])) {
                unset($interfaces[$i]);
            }
        }

        return $interfaces;
    }

    /**
     * Define interface
     *
     * @param array<string> $extends
     */
    protected function defineInterface(
        string $interface,
        array $extends
    ): void {
        if (interface_exists($interface)) {
            return;
        }

        $parts = explode('\\', ltrim($interface, '\\'));
        $name = array_pop($parts);
        $namespace = implode('\\', $parts);
        $parents = implode(',', $extends);

        $this->interfaceDefs[$interface] = 'namespace ' . $namespace . ' { interface ' . $name . ' extends ' . $parents . ' {} }';
    }


    /**
     * Compile definitions using eval()
     */
    protected function compileDefinitions(): string
    {
        $defs = implode("\n", $this->interfaceDefs);

        // Put the eval code in $GLOBALS to dump if it dies
        $GLOBALS['__eval'] = $defs . "\n" . $this->exceptionDef;

        eval($defs);
        $hash = md5($this->exceptionDef);

        if (!isset(self::$instances[$hash])) {
            self::$instances[$hash] = eval($this->exceptionDef);
        }

        // Remove defs from $GLOBALS again
        unset($GLOBALS['__eval']);

        return $hash;
    }
}
