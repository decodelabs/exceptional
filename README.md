# Exceptional

[![PHP from Packagist](https://img.shields.io/packagist/php-v/decodelabs/exceptional?style=flat)](https://packagist.org/packages/decodelabs/exceptional)
[![Latest Version](https://img.shields.io/packagist/v/decodelabs/exceptional.svg?style=flat)](https://packagist.org/packages/decodelabs/exceptional)
[![Total Downloads](https://img.shields.io/packagist/dt/decodelabs/exceptional.svg?style=flat)](https://packagist.org/packages/decodelabs/exceptional)
[![GitHub Workflow Status](https://img.shields.io/github/actions/workflow/status/decodelabs/exceptional/integrate.yml?branch=develop)](https://github.com/decodelabs/exceptional/actions/workflows/integrate.yml)
[![PHPStan](https://img.shields.io/badge/PHPStan-enabled-44CC11.svg?longCache=true&style=flat)](https://github.com/phpstan/phpstan)
[![License](https://img.shields.io/packagist/l/decodelabs/exceptional?style=flat)](https://packagist.org/packages/decodelabs/exceptional)


### Better Exceptions for PHP

Exceptional aims to offer a radically enhanced Exception framework that decouples the _meaning_ of an Exception from the underlying _implementation_ functionality.

_Get news and updates on the [DecodeLabs blog](https://blog.decodelabs.com)._

---

## Installation

Install via Composer:

```bash
composer require decodelabs/exceptional
```

## Usage

Exceptional exceptions can be used to greatly simplify how you generate and throw errors in your code, especially if you are writing a shared library.

Pass the name of your intended exception as a static call to the Exceptional base class and have a dynamic exception class created based on the most appropriate PHP Exception class along with a set of related interfaces for easier catching.

```php
use DecodeLabs\Exceptional;

// Create OutOfBoundsException
throw Exceptional::OutOfBounds('This is out of bounds');


// Implement multiple interfaces
throw Exceptional::{'NotFound,BadMethodCall'}(
    "Didn't find a thing, couldn't call the other thing"
);

// You can associate a http code too..
throw Exceptional::CompletelyMadeUpMeaning(
    message: 'My message',
    code: 1234,
    http: 501
);

// Implement already existing Exception interfaces
throw Exceptional::{'InvalidArgument,Psr\\Cache\\InvalidArgumentException'}(
    message: 'Cache items must implement Cache\\IItem',
    http: 500,
    data: $item
);

// Reference interfaces using a path style
throw Exceptional::{'../OtherNamespace/OtherInterface'}('My exception');
```

Catch an Exceptional exception in the normal way using whichever scope you require:

```php
namespace MyNamespace;

try {
    throw Exceptional::{'NotFound,BadMethodCall'}(
        "Didn't find a thing, couldn't call the other thing"
    );
} catch(
    \Exception |
    \BadMethodCallException |
    Exceptional\Exception |
    Exceptional\NotFoundException |
    MyNamespace\NotFoundException |
    MyNamespace\BadMethodCallException
) {
    // All these types will catch
    dd($e);
}
```


### Traits

Custom functionality can be mixed in to the generated exception automatically by defining traits at the same namespace level as any of the interfaces being implemented.

```php
namespace MyLibrary;

trait BadThingExceptionTrait {

    public function getCustomData(): ?string {
        return $this->params['customData'] ?? null;
    }
}

class Thing {

    public function doAThing() {
        throw Exceptional::BadThing(
            message: 'A bad thing happened',
            data: [
                'customData' => 'My custom info'
            ]
        );
    }
}
```

## Other information
- [Rationale for Exceptional](docs/Rationale.md)
- [An explanation of how the Exceptional interface works](docs/HowItWorks.md)

