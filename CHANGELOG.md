# Changelog

All notable changes to this project will be documented in this file.<br>
The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

### Unreleased
--

---

### [v0.5.3](https://github.com/decodelabs/exceptional/commits/v0.5.3) - 14th February 2025

- Fixed $http and $code property hooks
- Updated dependencies

[Full list of changes](https://github.com/decodelabs/exceptional/compare/v0.5.2...v0.5.3)

---

### [v0.5.2](https://github.com/decodelabs/exceptional/commits/v0.5.2) - 11th February 2025

- Regained max PHPStan conformance

[Full list of changes](https://github.com/decodelabs/exceptional/compare/v0.5.1...v0.5.2)

---

### [v0.5.1](https://github.com/decodelabs/exceptional/commits/v0.5.1) - 11th February 2025

- Fixed PHP min version

[Full list of changes](https://github.com/decodelabs/exceptional/compare/v0.5.0...v0.5.1)

---

### [v0.5.0](https://github.com/decodelabs/exceptional/commits/v0.5.0) - 11th February 2025

- Standardised Factory interface
- Simplified parameter handling
- Updated stack frame handling
- Replaced getters with properties
- Imported PHPStan extension from shared package
- Added test for IncompleteException
- Upgraded PHPStan to v2
- Added PHP8.4 to CI workflow
- Made PHP8.4 the minimum version

[Full list of changes](https://github.com/decodelabs/exceptional/compare/v0.4.8...v0.5.0)

---

### [v0.4.8](https://github.com/decodelabs/exceptional/commits/v0.4.8) - 7th February 2025

- Removed ref to E_STRICT
- Fixed PHPStan signature visibility

[Full list of changes](https://github.com/decodelabs/exceptional/compare/v0.4.7...v0.4.8)

---

### [v0.4.7](https://github.com/decodelabs/exceptional/commits/v0.4.7) - 22nd August 2024

- Added generic call signature for PHPStan
- Added @phpstan-require-implements constraints

[Full list of changes](https://github.com/decodelabs/exceptional/compare/v0.4.6...v0.4.7)

---

### [v0.4.6](https://github.com/decodelabs/exceptional/commits/v0.4.6) - 21st August 2024

- Converted consts to protected PascalCase

[Full list of changes](https://github.com/decodelabs/exceptional/compare/v0.4.5...v0.4.6)

---

### [v0.4.5](https://github.com/decodelabs/exceptional/commits/v0.4.5) - 24th March 2024

- Use Wellspring for the Autoloader
- Made PHP8.1 minimum version

[Full list of changes](https://github.com/decodelabs/exceptional/compare/v0.4.4...v0.4.5)

---

### [v0.4.4](https://github.com/decodelabs/exceptional/commits/v0.4.4) - 26th September 2023

- Migrated to use effigy in CI workflow
- Fixed PHP8.1 testing

[Full list of changes](https://github.com/decodelabs/exceptional/compare/v0.4.3...v0.4.4)

---

### [v0.4.3](https://github.com/decodelabs/exceptional/commits/v0.4.3) - 17th October 2022

- Added interface and trait exists check in Autoloader
- Updated composer check script
- Updated CI environment

[Full list of changes](https://github.com/decodelabs/exceptional/compare/v0.4.2...v0.4.3)

---

### [v0.4.2](https://github.com/decodelabs/exceptional/commits/v0.4.2) - 23rd August 2022

- Added zend.exception_ignore_args override
- Added concrete types to all members

[Full list of changes](https://github.com/decodelabs/exceptional/compare/v0.4.1...v0.4.2)

---

### [v0.4.1](https://github.com/decodelabs/exceptional/commits/v0.4.1) - 23rd August 2022

- Added extra class_exists check in Autoloader

[Full list of changes](https://github.com/decodelabs/exceptional/compare/v0.4.0...v0.4.1)

---

### [v0.4.0](https://github.com/decodelabs/exceptional/commits/v0.4.0) - 22nd August 2022

- Removed PHP7 compatibility
- Updated ECS to v11
- Updated PHPUnit to v9

[Full list of changes](https://github.com/decodelabs/exceptional/compare/v0.3.3...v0.4.0)

---

### [v0.3.3](https://github.com/decodelabs/exceptional/commits/v0.3.3) - 9th March 2022

- Transitioned from Travis to GHA
- Updated PHPStan and ECS dependencies

[Full list of changes](https://github.com/decodelabs/exceptional/compare/v0.3.2...v0.3.3)

---

### [v0.3.2](https://github.com/decodelabs/exceptional/commits/v0.3.2) - 2nd April 2021

- Fixed ExceptionTrait interface
- Added test for ExceptionTrait

[Full list of changes](https://github.com/decodelabs/exceptional/compare/v0.3.1...v0.3.2)

---

### [v0.3.1](https://github.com/decodelabs/exceptional/commits/v0.3.1) - 2nd April 2021

- Updated for max PHPStan conformance

[Full list of changes](https://github.com/decodelabs/exceptional/compare/v0.3.0...v0.3.1)

---

### [v0.3.0](https://github.com/decodelabs/exceptional/commits/v0.3.0) - 18th March 2021

- Implement PreparedTraceException from Glitch
- Enabled PHP8 testing

[Full list of changes](https://github.com/decodelabs/exceptional/compare/v0.2.9...v0.3.0)

---

### [v0.2.9](https://github.com/decodelabs/exceptional/commits/v0.2.9) - 6th October 2020

- Fixed incomplete() rewind handling
- Updated Veneer handling in Frame
- Applied full PSR12 standards
- Added PSR12 check to Travis build

[Full list of changes](https://github.com/decodelabs/exceptional/compare/v0.2.8...v0.2.9)

---

### [v0.2.8](https://github.com/decodelabs/exceptional/commits/v0.2.8) - 2nd October 2020

- Switched to Glitch Proxy for Path Normalizer

[Full list of changes](https://github.com/decodelabs/exceptional/compare/v0.2.7...v0.2.8)

---

### [v0.2.7](https://github.com/decodelabs/exceptional/commits/v0.2.7) - 2nd October 2020

- Added incomplete() shortcut

[Full list of changes](https://github.com/decodelabs/exceptional/compare/v0.2.6...v0.2.7)

---

### [v0.2.6](https://github.com/decodelabs/exceptional/commits/v0.2.6) - 2nd October 2020

- Fixed ErrorException support
- Added dump handling for ErrorException severity

[Full list of changes](https://github.com/decodelabs/exceptional/compare/v0.2.5...v0.2.6)

---

### [v0.2.5](https://github.com/decodelabs/exceptional/commits/v0.2.5) - 2nd October 2020

- Ported readme from Glitch

[Full list of changes](https://github.com/decodelabs/exceptional/compare/v0.2.4...v0.2.5)

---

### [v0.2.4](https://github.com/decodelabs/exceptional/commits/v0.2.4) - 30th September 2020

- Fixed AutoLoader classname check

[Full list of changes](https://github.com/decodelabs/exceptional/compare/v0.2.3...v0.2.4)

---

### [v0.2.3](https://github.com/decodelabs/exceptional/commits/v0.2.3) - 30th September 2020

- Added initial Exception auto loader

[Full list of changes](https://github.com/decodelabs/exceptional/compare/v0.2.2...v0.2.3)

---

### [v0.2.2](https://github.com/decodelabs/exceptional/commits/v0.2.2) - 30th September 2020

- Fixed namespace format dereferencing

[Full list of changes](https://github.com/decodelabs/exceptional/compare/v0.2.1...v0.2.2)

---

### [v0.2.1](https://github.com/decodelabs/exceptional/commits/v0.2.1) - 30th September 2020

- Fixed type inference for existing interfaces

[Full list of changes](https://github.com/decodelabs/exceptional/compare/v0.2.0...v0.2.1)

---

### [v0.2.0](https://github.com/decodelabs/exceptional/commits/v0.2.0) - 29th September 2020

- Added initial Exception Factory structure
- Improved interface list handling
- Fixed param normalizing in ExceptionTrait

[Full list of changes](https://github.com/decodelabs/exceptional/compare/v0.1.0...v0.2.0)

---

### [v0.1.0](https://github.com/decodelabs/exceptional/commits/v0.1.0) - 29th September 2020

- Added initial Exception interface
