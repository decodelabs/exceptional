# Exceptional — Package Specification

> **Cluster:** `core`
> **Language:** `php`
> **Milestone:** `m1`
> **Repo:** `https://github.com/decodelabs/exceptional`
> **Role:** Enhanced exception framework that decouples exception meaning from implementation

This document describes the purpose, contracts, and design of **Exceptional** within the Decode Labs ecosystem.

It is aimed at:

- Developers **using** Exceptional in their own applications or libraries.
- Contributors **maintaining or extending** Exceptional.
- Tools and AI assistants that need to reason about its behaviour.

---

## 1. Overview

### 1.1 Purpose

Exceptional provides an enhanced exception framework for PHP that **decouples the meaning of an exception** from any single concrete exception class.

Instead of defining and maintaining a large hierarchy of bespoke exception classes, code can:

- Declare **the meaning** of a failure (e.g. "NotFound", "InvalidArgument", "Runtime") via static calls on `DecodeLabs\Exceptional`.
- Have Exceptional dynamically generate **appropriate exception classes and interfaces** that:
  - extend or map onto SPL exception types where appropriate, and
  - implement domain-specific interfaces for more precise catching.

This greatly simplifies how errors are generated and handled, especially in shared libraries and complex ecosystems like Decode Labs. It eliminates the need to manually create exception class hierarchies while maintaining type safety and flexible catch semantics.

### 1.2 Non-Goals

Exceptional does **not**:

- Provide a logging or reporting system by itself.
- Replace general-purpose error monitoring or observability tools.
- Implement framework-specific error pages or HTTP error responses (these are handled by higher-level packages using Exceptional as a building block).
- Manage exception lifecycle or recovery strategies beyond generation and representation.

Exceptional focuses purely on **how exceptions are represented and generated**, not on how they are displayed, logged, or handled at the application level.

---

## 2. Role in the Ecosystem

### 2.1 Cluster & Positioning

- **Cluster:** `core` (see Chorus taxonomy)
- Exceptional is a **foundational library** used by many other Decode Labs packages to express errors in a consistent and expressive way.

It sits at a low level in the dependency graph:

- It is safe to use from almost anywhere in the stack.
- It has no knowledge of HTTP, CLI, or framework specifics.
- Higher-level packages (HTTP stack, routing, storage, tooling, etc.) use Exceptional to:
  - raise meaningful errors,
  - offer precise catch-points,
  - avoid ad-hoc exception hierarchies.

### 2.2 Typical Usage Contexts

Typical places Exceptional appears:

- **Core libraries** (collections, IO, routing, config) throwing semantic errors.
- **HTTP and CLI runtimes**, wrapping failures from deeper layers.
- **Integration packages**, mapping remote/API failures into local exception semantics.
- **Validation and data processing**, signalling invalid inputs or processing failures.

Exceptional is intended to be used whenever a Decode Labs package needs to signal an error that should be:

- consistent in shape,
- rich in metadata,
- and easy to catch at different scopes.

---

## 3. Public Surface

> This section focuses on the conceptual API, not every symbol.

### 3.1 Key Types

The primary public type is:

- `DecodeLabs\Exceptional`
  Static entry point used to generate and throw exceptions of a desired **meaning**. Uses `__callStatic()` to accept arbitrary exception names.

Generated types (created at runtime) include:

- `DecodeLabs\Exceptional\Exception`
  Base interface implemented by all Exceptional-generated exceptions. Extends `Throwable` and `PreparedTraceException` (from Remnant).

- `DecodeLabs\Exceptional\*Exception`
  Concrete exception classes and interfaces (e.g. `NotFoundException`, `InvalidArgumentException`) created on demand based on how Exceptional is called. These may appear in the caller's namespace as well as the global `DecodeLabs\Exceptional` namespace.

Internal types (used by the framework but not directly instantiated by users):

- `DecodeLabs\Exceptional\Factory`
  Handles dynamic exception class and interface generation.

- `DecodeLabs\Exceptional\Parameters`
  Value object storing exception metadata (message, code, HTTP status, severity, data, stack trace, etc.).

- `DecodeLabs\Exceptional\ExceptionTrait`
  Provides default implementation for the `Exception` interface, including property accessors and constructor logic.

- `DecodeLabs\Exceptional\AutoLoader`
  Registers with Wellspring to handle dynamic class loading for generated exception types.

The exact set of generated types depends on how Exceptional is invoked, the requested names, and the namespace context.

### 3.2 Main Entry Points

The main usage pattern is static calls on `DecodeLabs\Exceptional` using **exception meaning names**:

```php
use DecodeLabs\Exceptional;

// Create an OutOfBoundsException
throw Exceptional::OutOfBounds(
    'This is out of bounds'
);

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

Key concepts:

- Static method name(s) on `Exceptional` define the **meaning** of the error.
- Multiple names (comma-separated) request multiple interfaces to be implemented.
- Named arguments (e.g. `message`, `http`, `data`, `severity`) control the generated exception's properties and metadata.
- Path-style references (e.g. `../OtherNamespace/Interface`) allow relative namespace navigation.
- Exception names are automatically suffixed with "Exception" if not already present.

---

## 4. Dependencies

### 4.1 Direct Decode Labs Dependencies

From `composer.json`:

- `decodelabs/remnant` (^0.2.1)
  Used for:
  - `PreparedTraceException` interface (extended by `Exceptional\Exception`)
  - `Trace` and `Frame` classes for enhanced stack trace representation
  - Immutable parameter storage patterns

- `decodelabs/wellspring` (^0.2)
  Used for:
  - Autoloading infrastructure via `Wellspring::register()`
  - Dynamic class loading for generated exception types
  - Reflection helpers involved in exception class/interface generation and discovery

**Optional integration:**

- `decodelabs/monarch` (^0.2, via conflict constraint)
  If available at runtime, used to prettify file paths in exception string representations. This is an optional enhancement that does not affect core functionality.

### 4.2 External Dependencies

- `symfony/polyfill-mbstring` (^1.31)
  Ensures consistent multibyte string behaviour across environments, particularly for exception name parsing and interface generation.

Exceptional itself does not rely on any framework-specific runtime. It requires PHP 8.4 or higher.

---

## 5. Behaviour & Contracts

### 5.1 Invariants

- Calls to `DecodeLabs\Exceptional` **always return a throwable** implementing `DecodeLabs\Exceptional\Exception` representing the requested error meaning.
- For any given combination of:
  - calling namespace,
  - requested exception names and interfaces,
  - and parameters,

  Exceptional generates a class that:

  - extends a suitable SPL exception base (e.g. `\InvalidArgumentException`, `\RuntimeException`, `\LogicException`) when appropriate, and
  - implements one or more interfaces representing the semantic error meaning.

- Generated exception classes **implement `Exceptional\Exception`** and are safe to catch at that interface level.
- Generated exceptions **carry structured metadata** (e.g. `message`, `code`, `http`, `data`, `severity`) that can be inspected by callers and higher-level handlers.
- Exception class generation is **cached** by hash of the exception definition, so repeated calls with the same shape reuse the same class.
- All generated exceptions have access to enhanced stack traces via the `stackTrace` property (from Remnant).

### 5.2 Input & Output Contracts

- Static calls must use **valid PHP identifiers** (or comma-separated lists of identifiers) as method names when invoking Exceptional. Names starting with lowercase letters are rejected.
- Named arguments follow these conventions:
  - `message` – human-readable error message (string or Stringable, optional).
  - `code` – numeric code (int, optional).
  - `http` – associated HTTP status code (int, optional).
  - `severity` – error severity level (int, optional, used for `ErrorException`).
  - `data` – arbitrary payload(s) relevant to the error (mixed, optional).
  - `file` – override file path (string, optional).
  - `line` – override line number (int, optional).
  - `stackTrace` – override stack trace (Trace, optional).
  - `previous` – chained exception (Throwable, optional).
  - `namespace` – override target namespace (string, optional).
  - `interfaces` – additional interfaces to implement (array<string>, optional).
  - `traits` – additional traits to use (array<string>, optional).

- Positional arguments (0-12) are mapped to named parameters in order: message, code, http, severity, data, file, line, stackTrace, previous, type, namespace, interfaces, traits.

If invalid combinations are requested (e.g. impossible base type selection, non-existent trait references), Exceptional will throw an appropriate `InvalidArgumentException` or `LogicException` rather than silently degrading behaviour.

---

## 6. Error Handling

### 6.1 Exception Types

Exceptional-generated errors can be caught at multiple levels. For example:

```php
namespace MyNamespace;

use DecodeLabs\Exceptional;

try {
    throw Exceptional::{'NotFound,BadMethodCall'}(
        "Didn't find a thing, couldn't call the other thing"
    );
} catch (
    \Exception |
    \BadMethodCallException |
    Exceptional\Exception |
    Exceptional\NotFoundException |
    MyNamespace\NotFoundException |
    MyNamespace\BadMethodCallException
) {
    // All of these types will match the same thrown exception
}
```

Key guarantees:

- All Exceptional exceptions implement `Exceptional\Exception`.
- Depending on names and namespace, generated exceptions may also:
  - extend an SPL exception (e.g. `\InvalidArgumentException`, `\RuntimeException`),
  - implement global or namespaced interfaces,
  - appear under the caller's namespace (e.g. `MyNamespace\NotFoundException`).

Exceptional itself may throw:

- `BadMethodCallException` – when an invalid method name is used (e.g. lowercase first letter).
- `InvalidArgumentException` – when invalid parameters are provided (e.g. non-existent trait, conflicting base types).
- `LogicException` – when namespace context is required but unavailable (e.g. path-style references without namespace).

### 6.2 Error Strategy

Exceptional standardises error signalling across Decode Labs by:

- Encouraging **semantic** exception names (NotFound, InvalidArgument, etc.).
- Providing a single entry point (`Exceptional`) for throwing such exceptions.
- Avoiding proliferation of manually written exception classes and interfaces.
- Supporting multiple interface implementation for flexible catch strategies.
- Enabling namespace-aware exception generation for better type locality.

Higher-level packages are expected to:

- Use Exceptional to throw domain-appropriate errors.
- Document which meanings (names) they rely on as part of their public contract where relevant.
- Leverage trait mix-ins for domain-specific exception behaviour.

---

## 7. Configuration & Extensibility

### 7.1 Configuration

Exceptional has no runtime configuration surface aimed at end users; its behaviour is primarily defined by:

- The **names** passed to `Exceptional::…` calls.
- The **namespace** in which it is invoked (detected from call stack or explicitly provided).
- The presence of **traits and interfaces** in relevant namespaces (see below).

There is no global config file or environment-based configuration. The `helpers.php` file is automatically loaded via Composer autoload and registers the AutoLoader with Wellspring.

### 7.2 Extension Points

Exceptional supports extension via **traits** located in the same namespaces as implemented exception interfaces.

For example:

```php
namespace MyLibrary;

use DecodeLabs\Exceptional;

trait BadThingExceptionTrait
{
    public function getCustomData(): ?string
    {
        return $this->parameters->data['customData'] ?? null;
    }
}

class Thing
{
    public function doAThing(): void
    {
        throw Exceptional::BadThing(
            message: 'A bad thing happened',
            data: [
                'customData' => 'My custom info'
            ]
        );
    }
}
```

If a trait named `BadThingExceptionTrait` is found in the relevant namespace(s), Exceptional will **mix it into** the generated exception class, providing additional domain-specific behaviour.

This mechanism allows:

- Per-project or per-package custom behaviours on exceptions,
- Without having to manually define exception classes.
- Traits are discovered automatically based on namespace and naming conventions.

Additionally, users can:

- Provide explicit interface names via the `interfaces` parameter.
- Provide explicit trait names via the `traits` parameter.
- Use path-style references (e.g. `../OtherNamespace/Interface`) to reference interfaces in parent or sibling namespaces.

---

## 8. Interactions with Other Packages

Exceptional is designed to be depended on by many other packages:

- **HTTP stack (`harvest`)**
  Uses Exceptional to raise HTTP-related errors and propagate failures with appropriate HTTP status codes.

- **Config/data packages** (e.g. `dovetail`, `supermodel`, etc.)
  Use Exceptional for validation and configuration errors.

- **Integration packages**
  Wrap remote failures in locally meaningful Exceptional errors.

- **Runtime packages**
  Use Exceptional for system-level errors and component unavailability.

Design assumptions:

- Exceptional is available early in the stack and is considered **safe to use from any layer**.
- Other packages should not override Exceptional's core mechanisms, but may add **custom traits and interfaces** to extend generated exceptions in their own namespaces.
- The AutoLoader is registered automatically via `helpers.php` and should not be manually registered or unregistered by consuming code.

---

## 9. Usage Examples

### 9.1 Basic error

```php
use DecodeLabs\Exceptional;

throw Exceptional::InvalidArgument(
    message: 'Expected a positive integer',
    data: ['value' => $value]
);
```

### 9.2 Multiple meanings

```php
use DecodeLabs\Exceptional;

throw Exceptional::{'NotFound,BadMethodCall'}(
    "Didn't find a thing, couldn't call the other thing"
);
```

### 9.3 HTTP-aware errors

```php
use DecodeLabs\Exceptional;

throw Exceptional::Unauthorized(
    message: 'You must be logged in to access this resource',
    http: 401
);
```

### 9.4 Standard exception types

```php
use DecodeLabs\Exceptional;

// Uses standard SPL mapping
throw Exceptional::OutOfBounds('Index is out of bounds');
throw Exceptional::Domain('Invalid domain value');
throw Exceptional::Length('String is too long');
```

### 9.5 Custom traits

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

### 9.6 External interface implementation

```php
use DecodeLabs\Exceptional;

throw Exceptional::{'InvalidArgument,Psr\\Cache\\InvalidArgumentException'}(
    message: 'Cache items must implement Cache\\IItem',
    http: 500,
    data: $item
);
```

### 9.7 Catching at different scopes

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

---

## 10. Implementation Notes (For Contributors)

### 10.1 Internal Architecture

At a high level, Exceptional:

- Uses **reflection and naming conventions** to:
  - select appropriate SPL base classes (via `Factory::Standard` mapping),
  - generate exception class names in the caller's namespace,
  - generate and implement exception interfaces representing error meanings.
- Stores exception metadata in a `Parameters` value object (via Remnant patterns).
- Uses **dynamic class generation** via `eval()` to create anonymous exception classes that:
  - extend the appropriate SPL base,
  - implement requested interfaces,
  - use discovered traits.
- Caches generated classes by hash of their definition in `Factory::$instances`, avoiding repeated generation overhead.
- Registers an AutoLoader with Wellspring to handle loading of generated exception interfaces and classes.
- Temporarily disables the AutoLoader during exception generation to avoid circular dependencies.

Contributors should:

- Preserve the separation between **semantic meaning** (names/interfaces) and **implementation details** (base class, traits).
- Avoid introducing hard dependencies on framework-level concerns.
- Maintain the caching mechanism to ensure performance.
- Keep the `Factory::Standard` mapping up to date with common exception patterns.
- Ensure trait discovery logic respects namespace boundaries correctly.

### 10.2 Performance Considerations

- Dynamic class/interface generation has an upfront cost, but is typically amortised via caching in `Factory::$instances`.
- Hot paths should avoid creating excessive numbers of **distinct** exception shapes if possible (each unique shape generates a new class).
- The AutoLoader is registered with Wellspring at `Priority::Low` to avoid interfering with other autoloaders.
- Stack trace generation is deferred until accessed via the `stackTrace` property.

### 10.3 Gotchas & Historical Decisions

- Exceptional intentionally supports **multiple interfaces** per exception to enable flexible catching strategies.
- Trait mix-in is namespace-based and may pick up traits from both:
  - the package namespace (`DecodeLabs\Exceptional`),
  - and the caller's namespace (e.g. `MyNamespace`).
- Path-style interface references (e.g. `../OtherNamespace/Interface`) require a namespace context; they will fail if called from global scope.
- The `rewind` parameter controls how many stack frames to skip when capturing file/line information; it defaults to 1 to skip the Exceptional call itself.
- Exception names must start with an uppercase letter to be valid; lowercase names are rejected to avoid confusion with PHP keywords.
- The `helpers.php` file sets `zend.exception_ignore_args` to `'0'` to ensure exception arguments are preserved in stack traces.

Refer to the `docs/` folder in the repo (e.g. "Rationale" and "How it works") for deeper historical context and design decisions.

---

## 11. Testing & Quality

### 11.1 Testing Strategy

Tests should cover:

- Correct mapping from names to base classes and interfaces (including the `Factory::Standard` hierarchy).
- Trait application logic and namespace-based discovery.
- Behaviour of parameters (message, http, data, severity, etc.).
- Multiple interface implementation.
- Path-style interface references.
- Namespace detection and exception generation in caller's namespace.
- Caching behaviour (same exception shape should reuse cached class).
- Edge cases:
  - Missing traits (should throw `InvalidArgumentException`),
  - Conflicting base types (should throw `InvalidArgumentException`),
  - Invalid method names (should throw `BadMethodCallException`),
  - Path references without namespace context (should throw `LogicException`).

### 11.2 Quality Signals

From the Decode Labs package index (at time of writing):

- **Code:** Tracked centrally in Chorus
- **Readme:** Tracked centrally in Chorus
- **Docs:** Tracked centrally in Chorus
- **Tests:** Tracked centrally in Chorus

Exceptional is widely used across the Decode Labs ecosystem and should be treated as a **high-stability** core dependency. Breaking changes would affect many downstream packages.

---

## 12. Roadmap & Future Ideas

Non-binding ideas:

- Further documentation on:
  - mapping patterns to SPL types,
  - best practices for defining project-specific traits/interfaces,
  - performance characteristics of different exception shapes.
- Additional helper methods or constants for:
  - common HTTP errors,
  - common library-level error meanings.
- Potential integration with observability packages for richer error reporting, without coupling Exceptional directly to logging/monitoring.
- Consideration of PHP 8.4+ features that might simplify dynamic class generation.

---

## 13. References

- **Chorus docs:**
  - Architecture principles
  - Package taxonomy & clusters
  - Backwards compatibility strategy (once published)

- **Related packages:**
  - `decodelabs/remnant` (stack trace and parameter storage)
  - `decodelabs/wellspring` (autoloading infrastructure)
  - `decodelabs/monarch` (optional path prettification)

- **Repository:**
  - `https://github.com/decodelabs/exceptional`

- **Additional documentation:**
  - `docs/Rationale.md` – Design rationale and motivation
  - `docs/HowItWorks.md` – Technical explanation of the implementation

---

> This spec is intended to stay in sync with the **actual behaviour** of the package.
> When you make significant changes to the public surface or semantics, please update this document and, where applicable, add or update ADRs in Chorus.
