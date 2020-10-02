# Exceptional

### Better Exceptions for PHP

Exceptional aims to offer a radically enhanced Exception framework that decouples the _meaning_ of an Exception from the underlying _implementation_ functionality.

## Exceptions
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
throw Exceptional::CompletelyMadeUpMeaning('My message', [
    'code' => 1234,
    'http' => 501
]);

// Implement already existing Exception interfaces
throw Exceptional::{'InvalidArgument,Psr\\Cache\\InvalidArgumentException'}(
    'Cache items must implement Cache\\IItem',
    ['http' => 500],  // params
    $item             // data
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
        throw Exceptional::BadThing('A bad thing happened', [
            'customData' => 'My custom info'
        ]);
    }
}
```

## Other information
- [Rationale for Exceptional](docs/Rationale.md)
- [An explanation of how the Exceptional interface works](docs/HowItWorks.md)

