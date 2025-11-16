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

Exceptional provides an enhanced exception framework for PHP that **decouples the meaning of an exception** from any single concrete exception class.

Instead of defining and maintaining a large hierarchy of bespoke exception classes, code can:

- Declare **the meaning** of a failure (e.g. “NotFound”, “InvalidArgument”, “Runtime”) via static calls on `DecodeLabs\Exceptional`.
- Have Exceptional dynamically generate **appropriate exception classes and interfaces** that:
  - extend or map onto SPL exception types where appropriate, and
  - implement domain-specific interfaces for more precise catching.

This greatly simplifies how errors are generated and handled, especially in shared libraries and complex ecosystems like Decode Labs.

### 1.2 Non-Goals

Exceptional does **not**:

- Provide a logging or reporting system by itself.
- Replace general-purpose error monitoring or observability tools.
- Implement framework-specific error pages or HTTP error responses (these are handled by higher-level packages using Exceptional as a building block).

Exceptional focuses purely on **how exceptions are represented and generated**, not on how they are displayed or logged.

---

## 2. Role in the Ecosystem

### 2.1 Cluster & Positioning

- **Cluster:** `core`
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
  Static entry point used to generate and throw exceptions of a desired **meaning**.

Generated types (created at runtime) include:

- `DecodeLabs\Exceptional\Exception`
  Base interface implemented by all Exceptional-generated exceptions.

- `DecodeLabs\Exceptional\*Exception`
  Concrete exception classes and interfaces (e.g. `NotFoundException`, `InvalidArgumentException`) created on demand based on how Exceptional is called.

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
    http: 501
);
```

Key concepts:

- Static method name(s) on `Exceptional` define the **meaning** of the error.
- Multiple names (comma-separated) request multiple interfaces to be implemented.
- Named arguments (e.g. `message`, `http`, `data`) control the generated exception’s properties and metadata.

---

## 4. Dependencies

### 4.1 Direct Decode Labs Dependencies

From `packages.json` / composer:

- `decodelabs/remnant`
  Used for internal data structures (e.g. immutable parameter storage) and/or enhanced representation of exception payloads.

- `decodelabs/wellspring`
  Used for autoloading / reflection helpers involved in dynamic exception class/interface generation and discovery.

### 4.2 External Dependencies

- `symfony/polyfill-mbstring`
  Ensures consistent multibyte string behaviour across environments.

Exceptional itself does not rely on any framework-specific runtime.

---

## 5. Behaviour & Contracts

### 5.1 Invariants

- Calls to `DecodeLabs\Exceptional` **always return a throwable** representing the requested error meaning.
- For any given combination of:
  - calling namespace,
  - requested exception names and interfaces,
  - and parameters,

  Exceptional generates a class that:

  - extends a suitable SPL exception base (e.g. `\InvalidArgumentException`, `\RuntimeException`) when appropriate, and
  - implements one or more interfaces representing the semantic error meaning.

- Generated exception classes **implement `Exceptional\Exception`** and are safe to catch at that interface level.
- Generated exceptions **carry structured metadata** (e.g. `message`, `code`, `http`, `data`) that can be inspected by callers and higher-level handlers.

### 5.2 Input & Output Contracts

- Static calls must use **valid PHP identifiers** (or comma-separated lists of identifiers) as method names when invoking Exceptional.
- Named arguments follow the conventions established in the README:
  - `message` – human-readable error message.
  - `code` – numeric code (optional).
  - `http` – associated HTTP status code (optional).
  - `data` – arbitrary payload(s) relevant to the error (optional).

If invalid combinations are requested (e.g. impossible base type selection), Exceptional will throw an appropriate error itself rather than silently degrading behaviour.

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
  - extend an SPL exception,
  - implement global or namespaced interfaces,
  - appear under the caller’s namespace (e.g. `MyNamespace\NotFoundException`).

### 6.2 Error Strategy

Exceptional standardises error signalling across Decode Labs by:

- Encouraging **semantic** exception names (NotFound, InvalidArgument, etc.).
- Providing a single entry point (`Exceptional`) for throwing such exceptions.
- Avoiding proliferation of manually written exception classes and interfaces.

Higher-level packages are expected to:

- Use Exceptional to throw domain-appropriate errors.
- Document which meanings (names) they rely on as part of their public contract where relevant.

---

## 7. Configuration & Extensibility

### 7.1 Configuration

Exceptional has no runtime configuration surface aimed at end users; its behaviour is primarily defined by:

- The **names** passed to `Exceptional::…` calls.
- The **namespace** in which it is invoked.
- The presence of **traits and interfaces** in relevant namespaces (see below).

There is no global config file or environment-based configuration.

### 7.2 Extension Points

Exceptional supports extension via **traits** located in the same namespaces as implemented exception interfaces.

For example:

```php
namespace MyLibrary;

trait BadThingExceptionTrait
{
    public function getCustomData(): ?string
    {
        return $this->params['customData'] ?? null;
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

---

## 8. Interactions with Other Packages

Exceptional is designed to be depended on by many other packages:

- **HTTP stack (`harvest`)**
  Uses Exceptional to raise HTTP-related errors and propagate failures.

- **Config/data packages** (e.g. `dovetail`, `supermodel`, etc.)
  Use Exceptional for validation and configuration errors.

- **Integration packages**
  Wrap remote failures in locally meaningful Exceptional errors.

Design assumptions:

- Exceptional is available early in the stack and is considered **safe to use from any layer**.
- Other packages should not override Exceptional’s core mechanisms, but may add **custom traits and interfaces** to extend generated exceptions in their own namespaces.

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

### 9.4 Custom traits

```php
namespace MyApp;

use DecodeLabs\Exceptional;

trait PaymentExceptionTrait
{
    public function getPaymentId(): ?string
    {
        return $this->params['paymentId'] ?? null;
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

---

## 10. Implementation Notes (For Contributors)

### 10.1 Internal Architecture

At a high level, Exceptional:

- Uses **reflection and naming conventions** to:
  - select appropriate SPL base classes,
  - generate exception class names in the caller’s namespace,
  - generate and implement exception interfaces representing error meanings.
- Stores exception metadata (parameters, HTTP codes, etc.) in an internal structure (via Remnant/Wellspring helpers).
- Caches generated classes/interfaces for reuse, avoiding repeated generation overhead.

Contributors should:

- Preserve the separation between **semantic meaning** (names/interfaces) and **implementation details** (base class, traits).
- Avoid introducing hard dependencies on framework-level concerns.

### 10.2 Performance Considerations

- Dynamic class/interface generation has an upfront cost, but is typically amortised via caching.
- Hot paths should avoid creating excessive numbers of **distinct** exception shapes if possible.

### 10.3 Gotchas & Historical Decisions

- Exceptional intentionally supports **multiple interfaces** per exception to enable flexible catching strategies.
- Trait mix-in is namespace-based and may pick up traits from both:
  - the package namespace, and
  - the caller’s namespace.

Refer to the `docs/` folder in the repo (e.g. “Rationale” and “How it works”) for deeper historical context and design decisions.

---

## 11. Testing & Quality

### 11.1 Testing Strategy

- Unit tests cover:
  - correct mapping from names to base classes and interfaces,
  - trait application logic,
  - behaviour of parameters (message, http, data, etc.).
- Edge cases (multiple interfaces, missing traits, existing interface hierarchies) should have explicit tests.

### 11.2 Quality Signals

From the Decode Labs package index (at time of writing):

- **Code:** 4.0
- **Readme:** 3.0
- **Docs:** (spec evolving)
- **Tests:** (to be updated as coverage grows)

Exceptional is widely used and should be treated as a **high-stability** core dependency once v1 is reached.

---

## 12. Roadmap & Future Ideas

Non-binding ideas:

- Further documentation on:
  - mapping patterns to SPL types,
  - best practices for defining project-specific traits/interfaces.
- Additional helper methods for:
  - common HTTP errors,
  - common library-level error meanings.
- Potential integration with observability packages for richer error reporting, without coupling Exceptional directly to logging/monitoring.

---

## 13. References

- **Chorus docs:**
  - Architecture principles
  - Package taxonomy & clusters
  - Backwards compatibility strategy (once published)

- **Related packages:**
  - `decodelabs/remnant`
  - `decodelabs/wellspring`

- **Repository:**
  - `https://github.com/decodelabs/exceptional`
