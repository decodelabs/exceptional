# Exceptional — Package Specification

> **Cluster:** `core`
> **Language:** `php`
> **Milestone:** `m1`
> **Repo:** `https://github.com/decodelabs/exceptional`
> **Role:** Enhanced exceptions

This document describes the purpose, contracts, and design of **Exceptional** within the Decode Labs ecosystem.

It is aimed at:

- Developers **using** Exceptional in their own applications or libraries.
- Contributors **maintaining or extending** Exceptional.
- Tools and AI assistants that need to reason about its behaviour.

---

## 1. Overview

### 1.1 Purpose

Exceptional provides an enhanced exception framework for PHP that decouples the meaning of an exception from its implementation. Instead of defining and maintaining large hierarchies of bespoke exception classes, code can declare the meaning of a failure (e.g. "NotFound", "InvalidArgument", "Runtime") via static calls on `DecodeLabs\Exceptional`, and have Exceptional dynamically generate appropriate exception classes and interfaces that extend or map onto SPL exception types where appropriate and implement domain-specific interfaces for more precise catching. This greatly simplifies how errors are generated and handled, especially in shared libraries and complex ecosystems like Decode Labs. It eliminates the need to manually create exception class hierarchies while maintaining type safety and flexible catch semantics.

### 1.2 Non-Goals

Exceptional does **not**:

- Provide a logging or reporting system by itself
- Replace general-purpose error monitoring or observability tools
- Implement framework-specific error pages or HTTP error responses
- Manage exception lifecycle or recovery strategies beyond generation and representation
- Provide exception handling or recovery mechanisms
- Provide exception serialization or persistence
- Provide exception translation or localization
- Provide exception routing or dispatching

Exceptional focuses purely on how exceptions are represented and generated, not on how they are displayed, logged, or handled at the application level.

---

## 2. Role in the Ecosystem

### 2.1 Cluster & Positioning

- **Cluster:** `core` (see Chorus taxonomy)
- Exceptional is a foundational library used by many other Decode Labs packages to express errors in a consistent and expressive way. It sits at a low level in the dependency graph. It is safe to use from almost anywhere in the stack. It has no knowledge of HTTP, CLI, or framework specifics. Higher-level packages (HTTP stack, routing, storage, tooling, etc.) use Exceptional to raise meaningful errors, offer precise catch-points, and avoid ad-hoc exception hierarchies.

### 2.2 Typical Usage Contexts

Typical places Exceptional appears:

- Core libraries (collections, IO, routing, config) throwing semantic errors
- HTTP and CLI runtimes, wrapping failures from deeper layers
- Integration packages, mapping remote/API failures into local exception semantics
- Validation and data processing, signalling invalid inputs or processing failures
- Service containers and dependency injection, signalling configuration or resolution failures
- File system operations, signalling I/O errors
- Network operations, signalling connection or protocol errors
- Data transformation, signalling conversion or parsing errors

Exceptional is intended to be used whenever a Decode Labs package needs to signal an error that should be consistent in shape, rich in metadata, and easy to catch at different scopes.

---

## 3. Public Surface

> This section focuses on the conceptual API, not every symbol.

### 3.1 Key Types

The primary public types are:

- `DecodeLabs\Exceptional`
  Static entry point used to generate and throw exceptions of a desired meaning. Uses `__callStatic()` to accept arbitrary exception names. Provides static methods that map to exception types.

- `DecodeLabs\Exceptional\Exception`
  Base interface implemented by all Exceptional-generated exceptions. Extends `Throwable` and `PreparedTraceException` (from Remnant). Provides properties for `parameters`, `http`, and `data`.

- `DecodeLabs\Exceptional\ExceptionTrait`
  Trait providing default implementation for the `Exception` interface. Implements property accessors, constructor logic, stack trace generation, and string representation.

- `DecodeLabs\Exceptional\Parameters`
  Value object storing exception metadata (message, code, HTTP status, severity, data, stack trace, file, line, previous exception, type, namespace, interfaces, traits, rewind).

- `DecodeLabs\Exceptional\Factory`
  Factory class handling dynamic exception class and interface generation. Manages type mapping, interface indexing, definition building, and class caching.

- `DecodeLabs\Exceptional\AutoLoader`
  AutoLoader class that registers with Wellspring to handle dynamic class loading for generated exception types. Provides registration/unregistration methods and class loading logic.

- `DecodeLabs\Exceptional\IncompleteException`
  Interface for exceptions that have incomplete reflection information. Extends `Exception` and provides `reflection` property.

- `DecodeLabs\Exceptional\IncompleteExceptionTrait`
  Trait providing implementation for `IncompleteException` interface. Implements reflection property accessor.

- `DecodeLabs\PHPStan\ExceptionalReflectionExtension`
  PHPStan extension for static analysis of Exceptional calls. Provides method reflection for dynamic `__callStatic()` methods.

Generated types (created at runtime) include:

- `DecodeLabs\Exceptional\*Exception`
  Concrete exception interfaces (e.g. `NotFoundException`, `InvalidArgumentException`) created on demand based on how Exceptional is called. These may appear in the caller's namespace as well as the global `DecodeLabs\Exceptional` namespace.

- Anonymous exception classes
  Dynamically generated exception classes that extend appropriate SPL base classes and implement requested interfaces.

The exact set of generated types depends on how Exceptional is invoked, the requested names, and the namespace context.

### 3.2 Main Entry Points

The main usage pattern is static calls on `DecodeLabs\Exceptional` using exception meaning names:

```php
use DecodeLabs\Exceptional;

// Create an OutOfBoundsException
throw Exceptional::OutOfBounds('This is out of bounds');

// Implement multiple interfaces for flexible catching
throw Exceptional::{'NotFound,BadMethodCall'}(
    "Didn't find a thing, couldn't call the other thing"
);

// Associate HTTP codes and additional data
throw Exceptional::CompletelyMadeUpMeaning(
    message: 'My message',
    code: 1234,
    http: 501,
    data: ['context' => 'value']
);

// Reference external interfaces
throw Exceptional::{'InvalidArgument,Psr\\Cache\\InvalidArgumentException'}(
    message: 'Cache items must implement Cache\\IItem',
    http: 500,
    data: $item
);

// Use path-style interface references
throw Exceptional::{'../OtherNamespace/OtherInterface'}('My exception');
```

---

## 4. Dependencies

### 4.1 Decode Labs

- `decodelabs/remnant` (required)
  Used for `PreparedTraceException` interface (extended by `Exceptional\Exception`), `Trace` and `Frame` classes for enhanced stack trace representation, and immutable parameter storage patterns.

- `decodelabs/wellspring` (required)
  Used for autoloading infrastructure via `Wellspring::register()`, dynamic class loading for generated exception types, and reflection helpers involved in exception class/interface generation and discovery.

### 4.2 External

- `symfony/polyfill-mbstring` (required)
  Ensures consistent multibyte string behaviour across environments, particularly for exception name parsing and interface generation.

### 4.3 Optional Integrations

- `decodelabs/monarch` — Detected at runtime if installed, used for prettifying file paths in exception string representations via `Monarch::getPaths()->prettify()`.

---

## 5. Behaviour & Contracts

### 5.1 Invariants

- Calls to `DecodeLabs\Exceptional` always return a throwable implementing `DecodeLabs\Exceptional\Exception` representing the requested error meaning
- For any given combination of calling namespace, requested exception names and interfaces, and parameters, Exceptional generates a class that extends a suitable SPL exception base (e.g. `\InvalidArgumentException`, `\RuntimeException`, `\LogicException`) when appropriate, and implements one or more interfaces representing the semantic error meaning
- Generated exception classes implement `Exceptional\Exception` and are safe to catch at that interface level
- Generated exceptions carry structured metadata (e.g. `message`, `code`, `http`, `data`, `severity`) that can be inspected by callers and higher-level handlers
- Exception class generation is cached by hash of the exception definition, so repeated calls with the same shape reuse the same class
- All generated exceptions have access to enhanced stack traces via the `stackTrace` property (from Remnant)
- Exception names must start with an uppercase letter to be valid
- Multiple exception types can be specified via comma-separated list in curly braces
- Path-style interface references require a namespace context
- Trait mix-in is namespace-based and may pick up traits from both the package namespace and the caller's namespace
- The `rewind` parameter controls how many stack frames to skip when capturing file/line information
- Exception names are automatically suffixed with "Exception" if not already present
- Standard exception types are mapped to SPL exception classes via `Factory::Standard`
- Interface definitions are generated at every namespace level up the tree to the target namespace
- Trait discovery looks for traits named `{InterfaceName}Trait` in relevant namespaces

### 5.2 Input & Output Contracts

**Static Call Format:**
- Method name must be valid PHP identifier starting with uppercase letter
- Multiple names can be specified via comma-separated list in curly braces: `Exceptional::{'Type1,Type2'}()`
- Path-style references can be used: `Exceptional::{'../OtherNamespace/Interface'}()`
- Named arguments: `message` (string|Stringable, optional), `code` (int, optional), `http` (int, optional), `severity` (int, optional), `data` (mixed, optional), `file` (string, optional), `line` (int, optional), `stackTrace` (Trace, optional), `previous` (Throwable, optional), `namespace` (string, optional), `interfaces` (array<string>, optional), `traits` (array<string>, optional)
- Positional arguments (0-12) are mapped to named parameters in order: message, code, http, severity, data, file, line, stackTrace, previous, type, namespace, interfaces, traits
- Returns: `DecodeLabs\Exceptional\Exception` instance

**Exception Interface:**
- `parameters: Parameters` — Exception parameters value object
- `http: ?int` — Associated HTTP status code (read/write)
- `data: mixed` — Arbitrary payload relevant to the error (read/write)
- `stackTrace: Trace` — Enhanced stack trace (from Remnant)
- `stackFrame: ?Frame` — First frame of stack trace

**Parameters Object:**
- `message: ?string` — Human-readable error message
- `code: ?int` — Numeric code
- `http: ?int` — Associated HTTP status code
- `severity: ?int` — Error severity level (for ErrorException)
- `data: mixed` — Arbitrary payload
- `rewind: int` — Stack frames to skip (default: 0)
- `file: ?string` — File path
- `line: ?int` — Line number
- `stackTrace: ?Trace` — Stack trace override
- `previous: ?Throwable` — Chained exception
- `type: ?string` — Base exception type
- `namespace: ?string` — Target namespace
- `interfaces: array<string>` — Additional interfaces to implement
- `traits: array<string>` — Additional traits to use

**Standard Exception Types:**
- Logic: `LogicException`
- BadFunctionCall: `BadFunctionCallException` (extends Logic)
- BadMethodCall: `BadMethodCallException` (extends BadFunctionCall)
- Domain: `DomainException` (extends Logic)
- InvalidArgument: `InvalidArgumentException` (extends Logic)
- Length: `LengthException` (extends Logic)
- OutOfRange: `OutOfRangeException` (extends Logic)
- Definition: (extends Logic)
- Implementation: (extends Logic)
- NotImplemented: (extends Implementation, http: 501)
- Unsupported: (extends Logic)
- Runtime: `RuntimeException`
- OutOfBounds: `OutOfBoundsException` (extends Runtime)
- Overflow: `OverflowException` (extends Runtime)
- Range: `RangeException` (extends Runtime)
- Underflow: `UnderflowException` (extends Runtime)
- UnexpectedValue: `UnexpectedValueException` (extends Runtime)
- Io: (extends Runtime)
- Protocol: (extends Io)
- BadRequest: (extends Runtime, http: 400)
- Unauthorized: (extends Runtime, http: 401)
- Forbidden: (extends Unauthorized, http: 403)
- NotFound: (extends Runtime)
- ResourceNotFound: (extends NotFound, http: 404)
- Setup: (extends Runtime)
- ComponentUnavailable: (extends Setup)
- ServiceUnavailable: (extends Setup, http: 503)
- Error: `ErrorException`

---

## 6. Error Handling

- Invalid method names (lowercase first letter) throw `BadMethodCallException`
- Invalid parameters (non-existent trait, conflicting base types) throw `InvalidArgumentException`
- Missing namespace context for path-style references throws `LogicException`
- Invalid interface references throw `InvalidArgumentException`
- Invalid trait references throw `InvalidArgumentException`
- Conflicting base type definitions throw `InvalidArgumentException`
- Exception generation failures are handled gracefully with appropriate exceptions
- AutoLoader registration/unregistration is handled automatically via `helpers.php`
- Stack trace generation is deferred until accessed via `stackTrace` property
- File/line information is captured from debug backtrace with rewind support

---

## 7. Configuration & Extensibility

- No runtime configuration surface aimed at end users
- Behaviour is primarily defined by names passed to `Exceptional::…` calls
- Namespace is detected from call stack or explicitly provided
- Traits and interfaces are discovered automatically based on namespace and naming conventions
- Custom traits can be mixed in by defining `{InterfaceName}Trait` in relevant namespaces
- Custom interfaces can be referenced via `interfaces` parameter
- Custom traits can be referenced via `traits` parameter
- Path-style references allow relative namespace navigation
- AutoLoader is registered automatically via `helpers.php` with Wellspring at `Priority::Low`
- `zend.exception_ignore_args` is set to `'0'` in `helpers.php` to ensure exception arguments are preserved

---

## 8. Interactions with Other Packages

### 8.1 Remnant

Exceptional uses Remnant for:
- `PreparedTraceException` interface extended by `Exceptional\Exception`
- `Trace` and `Frame` classes for enhanced stack trace representation
- Immutable parameter storage patterns

### 8.2 Wellspring

Exceptional uses Wellspring for:
- Autoloading infrastructure via `Wellspring::register()`
- Dynamic class loading for generated exception types
- Reflection helpers for exception class/interface generation and discovery

### 8.3 Monarch

Monarch is optionally used by Exceptional for:
- Prettifying file paths in exception string representations
- Path formatting via `Monarch::getPaths()->prettify()`

### 8.4 Other Packages

Many Decode Labs packages use Exceptional for error handling:
- `decodelabs/coercion` — Type conversion errors
- `decodelabs/atlas` — File system errors
- `decodelabs/deliverance` — IO errors
- `decodelabs/eventful` — Event loop errors
- `decodelabs/commandment` — CLI command errors
- `decodelabs/clip` — CLI runtime errors
- `decodelabs/genesis` — Bootstrapping errors
- `decodelabs/glitch` — Error handling and reporting
- `decodelabs/archetype` — Class resolution errors
- `decodelabs/slingshot` — Dependency injection errors
- `decodelabs/pandora` — Service resolution errors
- `decodelabs/terminus` — CLI IO errors
- `decodelabs/lucid` — Validation errors
- `decodelabs/collections` — Collection operation errors
- And many others throughout the ecosystem

---

## 9. Usage Examples

### 9.1 Basic Error

```php
use DecodeLabs\Exceptional;

throw Exceptional::InvalidArgument(
    message: 'Expected a positive integer',
    data: ['value' => $value]
);
```

### 9.2 Multiple Meanings

```php
use DecodeLabs\Exceptional;

throw Exceptional::{'NotFound,BadMethodCall'}(
    "Didn't find a thing, couldn't call the other thing"
);
```

### 9.3 HTTP-Aware Errors

```php
use DecodeLabs\Exceptional;

throw Exceptional::Unauthorized(
    message: 'You must be logged in to access this resource',
    http: 401
);
```

### 9.4 Standard Exception Types

```php
use DecodeLabs\Exceptional;

// Uses standard SPL mapping
throw Exceptional::OutOfBounds('Index is out of bounds');
throw Exceptional::Domain('Invalid domain value');
throw Exceptional::Length('String is too long');
```

### 9.5 Custom Traits

```php
namespace MyApp;

use DecodeLabs\Exceptional;

trait PaymentExceptionTrait
{
    public function getPaymentId(): ?string
    {
        return $this->parameters->data['paymentId'] ?? null;
    }
}

function chargeCustomer(string $paymentId): void
{
    // Something goes wrong:
    throw Exceptional::Payment(
        message: 'Payment failed',
        data: [
            'paymentId' => $paymentId
        ]
    );
}
```

### 9.6 External Interface Implementation

```php
use DecodeLabs\Exceptional;

throw Exceptional::{'InvalidArgument,Psr\\Cache\\InvalidArgumentException'}(
    message: 'Cache items must implement Cache\\IItem',
    http: 500,
    data: $item
);
```

### 9.7 Catching at Different Scopes

```php
namespace MyNamespace;

use DecodeLabs\Exceptional;
use DecodeLabs\Exceptional\Exception as ExceptionalException;

try {
    throw Exceptional::NotFound('Resource not found');
} catch (ExceptionalException $e) {
    // Catch all Exceptional exceptions
    echo $e->message;
    echo $e->http; // May be null or set
    echo $e->data; // May be null or set
} catch (\MyNamespace\NotFoundException $e) {
    // Catch namespace-specific interface
} catch (\Exception $e) {
    // Catch any exception
}
```

### 9.8 Path-Style References

```php
namespace MyLibrary\SubNamespace;

use DecodeLabs\Exceptional;

// Reference interface in parent namespace
throw Exceptional::{'../OtherInterface'}('My exception');
```

### 9.9 Explicit Interfaces and Traits

```php
use DecodeLabs\Exceptional;

throw Exceptional::CustomError(
    message: 'Custom error',
    interfaces: ['MyNamespace\CustomInterface'],
    traits: ['MyNamespace\CustomTrait']
);
```

### 9.10 ErrorException

```php
use DecodeLabs\Exceptional;

throw Exceptional::Error(
    message: 'Fatal error occurred',
    severity: E_ERROR
);
```

---

## 10. Implementation Notes (for Contributors)

### 10.1 Internal Architecture

At a high level, Exceptional:
- Uses reflection and naming conventions to select appropriate SPL base classes (via `Factory::Standard` mapping), generate exception class names in the caller's namespace, and generate and implement exception interfaces representing error meanings
- Stores exception metadata in a `Parameters` value object (via Remnant patterns)
- Uses dynamic class generation via `eval()` to create anonymous exception classes that extend the appropriate SPL base, implement requested interfaces, and use discovered traits
- Caches generated classes by hash of their definition in `Factory::$instances`, avoiding repeated generation overhead
- Registers an AutoLoader with Wellspring to handle loading of generated exception interfaces and classes
- Temporarily disables the AutoLoader during exception generation to avoid circular dependencies

### 10.2 Type Mapping

The `Factory::Standard` mapping defines how exception names map to SPL base classes and HTTP status codes. This mapping is hierarchical (e.g. `BadMethodCall` extends `BadFunctionCall` which extends `Logic`). Standard types are automatically mapped to appropriate SPL exception classes.

### 10.3 Interface Generation

Interfaces are generated at multiple namespace levels:
- In the caller's namespace (e.g. `MyNamespace\NotFoundException`)
- In parent namespaces up the tree (e.g. `MyNamespace\Exception`)
- In the Exceptional namespace for standard types (e.g. `DecodeLabs\Exceptional\NotFoundException`)

### 10.4 Trait Discovery

Traits are discovered automatically by looking for `{InterfaceName}Trait` in:
- The caller's namespace
- Parent namespaces up the tree
- The Exceptional namespace

### 10.5 Caching Strategy

Generated exception classes are cached by MD5 hash of their definition. This ensures that repeated calls with the same exception shape reuse the same class, improving performance.

### 10.6 Stack Trace Handling

Stack traces are generated using `debug_backtrace()` with rewind support. The `rewind` parameter controls how many stack frames to skip. Stack trace generation is deferred until accessed via the `stackTrace` property.

### 10.7 AutoLoader Integration

The AutoLoader is registered with Wellspring at `Priority::Low` to avoid interfering with other autoloaders. It handles loading of generated exception interfaces and classes. The AutoLoader is temporarily disabled during exception generation to avoid circular dependencies.

### 10.8 PHPStan Integration

The PHPStan extension provides method reflection for dynamic `__callStatic()` methods, allowing static analysis tools to understand Exceptional calls.

### 10.9 Performance Considerations

- Dynamic class/interface generation has an upfront cost, but is typically amortised via caching in `Factory::$instances`
- Hot paths should avoid creating excessive numbers of distinct exception shapes if possible (each unique shape generates a new class)
- The AutoLoader is registered with Wellspring at `Priority::Low` to avoid interfering with other autoloaders
- Stack trace generation is deferred until accessed via the `stackTrace` property

### 10.10 Gotchas & Historical Decisions

- Exceptional intentionally supports multiple interfaces per exception to enable flexible catching strategies
- Trait mix-in is namespace-based and may pick up traits from both the package namespace and the caller's namespace
- Path-style interface references (e.g. `../OtherNamespace/Interface`) require a namespace context; they will fail if called from global scope
- The `rewind` parameter controls how many stack frames to skip when capturing file/line information; it defaults to 1 to skip the Exceptional call itself
- Exception names must start with an uppercase letter to be valid; lowercase names are rejected to avoid confusion with PHP keywords
- The `helpers.php` file sets `zend.exception_ignore_args` to `'0'` to ensure exception arguments are preserved in stack traces
- Exception names are automatically suffixed with "Exception" if not already present
- Standard exception types are mapped to SPL exception classes via `Factory::Standard`
- Interface definitions are generated at every namespace level up the tree to the target namespace
- Trait discovery looks for traits named `{InterfaceName}Trait` in relevant namespaces

---

## 11. Testing & Quality

- **Code Quality Score:** 4/5
- **README Quality Score:** 3/5
- **Documentation Score:** 0/5 (this spec)
- **Test Coverage Score:** 0/5

See `composer.json` for supported PHP versions.

---

## 12. Roadmap & Future Ideas

- Further documentation on mapping patterns to SPL types
- Best practices for defining project-specific traits/interfaces
- Performance characteristics of different exception shapes
- Additional helper methods or constants for common HTTP errors
- Common library-level error meanings
- Potential integration with observability packages for richer error reporting
- Consideration of PHP 8.4+ features that might simplify dynamic class generation
- Improved PHPStan integration for better static analysis
- Enhanced trait discovery mechanisms
- Better error messages for invalid exception configurations
- Support for exception serialization
- Support for exception translation/localization
- Performance optimizations for hot paths

---

## 13. References

- [Remnant Package](https://github.com/decodelabs/remnant) — Stack trace and parameter storage
- [Wellspring Package](https://github.com/decodelabs/wellspring) — Autoloading infrastructure
- [Monarch Package](https://github.com/decodelabs/monarch) — Optional path prettification
- [Chorus Package Index](../../../chorus/config/packages.json) — Ecosystem metadata
- [Rationale Documentation](Rationale.md) — Design rationale and motivation
- [How It Works Documentation](HowItWorks.md) — Technical explanation of the implementation
- [PHP Exception Documentation](https://www.php.net/manual/en/language.exceptions.php) — PHP exception system
- [SPL Exceptions](https://www.php.net/manual/en/spl.exceptions.php) — Standard PHP Library exceptions
