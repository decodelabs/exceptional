<?php

/**
 * @package Exceptional
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Exceptional;

use DecodeLabs\Exceptional\Exception as ExceptionInterface;
use DecodeLabs\Remnant\Trace;
use Exception as RootException;
use InvalidArgumentException;
use LogicException;
use Throwable;

class Factory
{
    protected const Standard = [
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


    protected const Rewind = 2;

    /**
     * @var array<string,Exception>
     */
    private static array $instances = [];


    protected Parameters $parameters;


    protected ?string $namespace = null;

    /**
     * @var array<string,bool>
     */
    private array $interfaces = [];

    /**
     * @var array<string,bool>
     */
    private array $traits = [];


    /**
     * @var array<string, array<string>>
     */
    private array $interfaceIndex = [];

    /**
     * @var array<string, string>
     */
    private array $interfaceDefs = [];

    private string $exceptionDef;
    private bool $autoLoad = false;


    /**
     * @param list<string> $types
     * @param list<string> $interfaces
     * @param list<string> $traits
     */
    public static function create(
        array $types,
        ?string $message = null,
        ?int $http = null,
        ?int $code = null,
        ?int $severity = null,
        mixed $data = null,
        ?int $rewind = 0,
        ?string $file = null,
        ?int $line = null,
        ?Trace $stackTrace = null,
        ?Throwable $previous = null,
        ?string $namespace = null,
        ?array $interfaces = null,
        ?array $traits = null
    ): Exception {
        return new self(
            types: $types,
            message: $message,
            http: $http,
            code: $code,
            severity: $severity,
            data: $data,
            rewind: $rewind,
            file: $file,
            line: $line,
            stackTrace: $stackTrace,
            previous: $previous,
            namespace: $namespace,
            interfaces: $interfaces,
            traits: $traits
        )->build();
    }

    /**
     * @param list<string> $types
     * @param list<string> $interfaces
     * @param list<string> $traits
     */
    protected function __construct(
        array $types,
        ?string $message,
        ?int $http,
        ?int $code,
        ?int $severity,
        mixed $data,
        ?int $rewind,
        ?string $file,
        ?int $line,
        ?Trace $stackTrace,
        ?Throwable $previous,
        ?string $namespace,
        ?array $interfaces,
        ?array $traits
    ) {
        // Turn off autoloading
        $this->autoLoad = AutoLoader::isRegistered();
        AutoLoader::unregister();

        $this->parameters = $params = new Parameters(
            message: $message ?? 'Undefined error',
            code: $code,
            http: $http,
            severity: $severity,
            data: $data,
            rewind: $rewind ?? 1,
            stackTrace: $stackTrace,
            previous: $previous,
            namespace: $namespace,
            interfaces: $interfaces,
            traits: $traits
        );

        // Params
        $rewind = $params->rewind;
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, (int)($rewind + self::Rewind + 1));
        $key = $rewind + self::Rewind;
        $lastTrace = $trace[$key - 1];

        $params->file = $file ?? $lastTrace['file'] ?? null;
        $params->line = $line ?? $lastTrace['line'] ?? null;

        // Namespace
        $this->prepareTargetNamespace(
            $params->namespace,
            $trace[$key] ?? null
        );

        // Inheritance
        $this->interfaceIndex['\\DecodeLabs\\Exceptional\\Exception'] = [];
        $this->traits['\\DecodeLabs\\Exceptional\\ExceptionTrait'] = true;

        $this->importTypes($types);
        $this->importInterfaces($params->interfaces);
        $this->importTraits($params->traits);
    }


    /**
     * @param array<string,mixed>|null $frame
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
            $class = $frame['class'] ?? null;

            if (!is_string($class)) {
                $class = null;
            }

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
     * @param array<string> $types
     */
    protected function importTypes(
        array $types
    ): void {
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
                if ($this->parameters->type !== null) {
                    throw new InvalidArgumentException(
                        'Exception has already defined base type: ' . $this->parameters->type
                    );
                }

                $this->parameters->type = trim($type, '\\');
            }

            // Ensure root slash
            if (substr($type, 0, 1) !== '\\') {
                $type = '\\' . $type;
            }

            $this->interfaces[$type] = true;
        }
    }


    /**
     * @param array<string> $interfaces
     */
    protected function importInterfaces(
        array $interfaces
    ): void {
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
     * @param array<string> $traits
     */
    protected function importTraits(
        array $traits
    ): void {
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
        $this->parameters->interfaces = $interfaces;
        $this->parameters->traits = array_keys($this->traits);

        // Reenable AutoLoader
        if ($this->autoLoad) {
            AutoLoader::register();
        }

        // Instantiate
        return new self::$instances[$hash]($this->parameters);
    }

    protected function indexInterface(
        string $interface
    ): void {
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
                $this->parameters->type !== null &&
                $this->parameters->type !== $baseClass
            ) {
                throw new InvalidArgumentException(
                    'Exception has already defined base type: ' . $this->parameters->type
                );
            }

            $this->parameters->type = $baseClass;
        }


        // Package
        if (isset(self::Standard[$name])) {
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
     * @param array<string> $parts
     */
    protected function indexNamespaceInterfaces(
        array $parts
    ): ?string {
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


    protected function indexPackageInterface(
        string $name
    ): void {
        $standard = self::Standard[$name] ?? [];
        $prefix = '\\DecodeLabs\\Exceptional\\';
        $interface = $prefix . $name . 'Exception';

        // Check
        $classExists = class_exists($interface);

        // Base class
        if (
            isset($standard['type']) &&
            $this->parameters->type === null
        ) {
            $this->parameters->type = $standard['type'];
        }

        // Http
        if (
            isset($standard['http']) &&
            !isset($this->parameters->http)
        ) {
            $this->parameters->http = $standard['http'];
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
     * @return list<string>
     */
    protected function buildDefinitions(): array
    {
        // Ensure base class
        if ($this->parameters->type === null) {
            $this->parameters->type = RootException::class;
        }

        // Create definitions for needed interfaces
        foreach ($this->interfaceIndex as $interface => $extends) {
            if (!empty($extends)) {
                $this->defineInterface($interface, $extends);
            }
        }


        // Build class def
        $this->exceptionDef = 'return new class() extends ' . $this->parameters->type;
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

        return array_values($interfaces);
    }

    /**
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


    protected function compileDefinitions(): string
    {
        $defs = implode("\n", $this->interfaceDefs);

        // Put the eval code in $GLOBALS to dump if it dies
        $GLOBALS['__eval'] = $defs . "\n" . $this->exceptionDef;

        eval($defs);
        $hash = md5($this->exceptionDef);

        if (!isset(self::$instances[$hash])) {
            /** @var Exception $blueprint */
            $blueprint = eval($this->exceptionDef);
            self::$instances[$hash] = $blueprint;
        }

        // Remove defs from $GLOBALS again
        unset($GLOBALS['__eval']);

        return $hash;
    }
}
