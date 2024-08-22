* Added generic call signature for PHPStan
* Added @phpstan-require-implements constraints

## v0.4.6 (2024-08-21)
* Converted consts to protected PascalCase

## v0.4.5 (2024-03-24)
* Use Wellspring for the Autoloader
* Made PHP8.1 minimum version

## v0.4.4 (2023-09-26)
* Migrated to use effigy in CI workflow
* Fixed PHP8.1 testing

## v0.4.3 (2022-10-17)
* Added interface and trait exists check in Autoloader
* Updated composer check script
* Updated CI environment

## v0.4.2 (2022-08-23)
* Added zend.exception_ignore_args override
* Added concrete types to all members

## v0.4.1 (2022-08-23)
* Added extra class_exists check in Autoloader

## v0.4.0 (2022-08-22)
* Removed PHP7 compatibility
* Updated ECS to v11
* Updated PHPUnit to v9

## v0.3.3 (2022-03-09)
* Transitioned from Travis to GHA
* Updated PHPStan and ECS dependencies

## v0.3.2 (2021-04-02)
* Fixed ExceptionTrait interface
* Added test for ExceptionTrait

## v0.3.1 (2021-04-02)
* Updated for max PHPStan conformance

## v0.3.0 (2021-03-18)
* Implement PreparedTraceException from Glitch
* Enabled PHP8 testing

## v0.2.9 (2020-10-06)
* Fixed incomplete() rewind handling
* Updated Veneer handling in Frame
* Applied full PSR12 standards
* Added PSR12 check to Travis build

## v0.2.8 (2020-10-02)
* Switched to Glitch Proxy for Path Normalizer

## v0.2.7 (2020-10-02)
* Added incomplete() shortcut

## v0.2.6 (2020-10-02)
* Fixed ErrorException support
* Added dump handling for ErrorException severity

## v0.2.5 (2020-10-02)
* Ported readme from Glitch

## v0.2.4 (2020-09-30)
* Fixed AutoLoader classname check

## v0.2.3 (2020-09-30)
* Added initial Exception auto loader

## v0.2.2 (2020-09-30)
* Fixed namespace format dereferencing

## v0.2.1 (2020-09-30)
* Fixed type inference for existing interfaces

## v0.2.0 (2020-09-29)
* Added initial Exception Factory structure
* Improved interface list handling
* Fixed param normalizing in ExceptionTrait

## v0.1.0 (2020-09-29)
* Added initial Exception interface
