# How the Exceptional interface works

The libraries main aim is to generate dynamic Exceptions based on a set of criteria in any particular context, with minimum boilerplate code.

## Calling the exception generator

Exceptional combines a number of techniques to create a predictable and easy to use interface to the Exception generator mechanism.

Primarily, the main <code>Exceptional</code> static class provides a <code>__callStatic()</code> method that acts as a go-between to the Exception factory.

One major benefit of this structure is making use of the ability to pass arbitrary strings as method names to <code>__callStatic()</code>.

Exceptional uses this feature as a means of passing through the projected _type_ of exception to be generated, and parses that method name out to expand commas into an array:

```php
Exceptional::{'AnythingGoesHere,BadMethodCall'}('Test exception');

// Internally
// $types = ['AnythingGoesHereException', 'BadMethodCallException'];
```


## Calling the factory
It is the sole responsibility of the Factory to actually generate an instance of an Exception for the calling code to throw.

It uses a combination of <code>eval()</code> and anonymous classes to build a custom class specific to the current context containing a mix of interfaces and traits, to define type, message and functionality.

### Stack frame
The exception Factory uses <code>debug_backtrace()</code> to work out the namespace from which Exceptional was called and uses this to decide which interfaces need to be generated and what needs to be rolled into the final Exception class.

It's aim is to have an interface named with each of the types defined in the original call to the Factory (eg <code>Runtime</code>, <code>NotFound</code>) defined _within the namespace of the originating call_ so that <code>catch</code> blocks can reference the type directly.

```php
namespace Any\Old\Namespace;
use DecodeLabs\Exceptional;

try {
    throw Exceptional::Runtime('message');
} catch(
    \RuntimeException |
    RuntimeException |
    Any\Old\Namespace\RuntimeException $e
) {
    // do something
}
```

Secondary to that, if the requested types are listed as primary exception types by the Factory then there will also be an interface to represent it in the Exceptional namespace:

```php
namespace Any\Old\Namespace;
use DecodeLabs\Exceptional;

try {
    throw Exceptional::Runtime('message');
} catch(Exceptional\RuntimeException $e) {
    // do something
}
```

On top of that, the Factory will ensure there is an interface named <code>Exception</code> at _every_ namespace level up the tree to the target namespace (so long as that name is free in that context) so that developers can choose the granularity of catch blocks, ad hoc:

```php
namespace Any\Old\Namespace;

use MyLibrary\InnerNamespace\SomeClass;

$myLibrary = new SomeClass();

try {
    // This method will throw an Exceptional Exception
    $myLibrary->doAThing();
} catch(
    MyLibrary\InnerNamespace\Exception $e |
    MyLibrary\Exception $e |
    Exceptional\Exception $e
) {
    // All of the above tests will match
}
```

To increase compatibility with SPL exceptions, any types that have a corresponding SPL Exception class will extend from _that_ type, rather than the root Exception class:

```php
namespace Any\Old\Namespace;
use DecodeLabs\Exceptional;

try {
    throw Exceptional::Runtime('message');
} catch(\RuntimeException $e) {
    // do something
}
```


And then for _any_ interface that is added to the final type definition, the equivalent <code>\<InterfaceName>Trait</code> trait will be added too, if it exists. This allows the inclusion of context specific functionality within a specific category of Exceptions without having to tie the functionality to a particular meaning.


As an example, given the fallowing Exceptional call:

```php
namespace MyVendor\MyLibrary\SubFunctions;
use DecodeLabs\Exceptional;

trait RuntimeExceptionTrait {

    public function extraFunction() {
        return 'hello world';
    }
}

try {
    throw Exceptional::Runtime('message');
} catch(RuntimeException $e) {
    echo $e->extraFunction();
}
```

The resulting anonymous class will include:

- <code>MyVendor\MyLibrary\SubFunctions\RuntimeException</code> interface
- <code>MyVendor\MyLibrary\SubFunctions\RuntimeExceptionTrait</code> trait, with <code>extraFunction()</code>
- <code>DecodeLabs\Exceptional\RuntimeException</code> interface
- <code>MyVendor\MyLibrary\SubFunctions\Exception</code> interface
- <code>MyVendor\MyLibrary\Exception</code> interface
- <code>MyVendor\Exception</code> interface
- <code>RuntimeException</code> base class


#### Repeated execution

Once the Factory has generated an Exception for a particular subgroup of requested types within a specific namespace, it is hashed and cached so that repeated calls to the Factory within the same context can just return a new instance of the anonymous class. The resulting performance overhead of general usage of Exception exceptions then tends to be trivial, while the _development_ overhead is **massively** reduced as there is no need to define individual Exception classes for every type of error in all of your libraries.
