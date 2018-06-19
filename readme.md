# Generics

A PHP-based implementation of generics (templated classes) to aid with stricter type consistency.

## Table of Contents

- [Installation](#installation)
- [Usage Guide](#usage-guide)
    - [Motivation Behind the Library](#motivation-behind-the-library)
    - [Run Without Type Checks](#run-without-type-checks)
    - [Creating Custom Generics](#creating-custom-generics)
- [Licensing](#licensing)

## Installation

The package installs into a PHP application like any other PHP package:

```bash
composer require artisansdk/generic
```

## Usage Guide

For an example see `tests/Example.php` and run the following (use of reflection
in the performance tests requires an increase to default memory limits).

```bash
php -d memory_limit=256M tests/Example.php
```

### Motivation Behind the Library

Generics (or templated classes) come up a lot in systems that have lots of classes
that all represent a certain type. Games for example have a lot of bad guys and
weapons that often have similar behavior. In order to satisfy type checking in PHP
this would mean creating hundreds of classes and either creating complex inheritance
hierarchy or keeping it flat but repeating a lot of logic. Use of traits can help
but introduces other complexities. Generics are the answer. Having a generic bad
guy or weapon class from which you can define hundreds of others and ensure that
they all behave consistently and that the wrong kind of ammo cannot be loaded into
the wrong kind of weapon. Most languages would implement this with custom syntax
but in PHP we have to resort to user-land code. With a little reflection magic
and some clever composition though we can accomplish largely the same outcomes.

> **Further Reading:** There was an RFC for generics in PHP but it got skipped
over for lack of support. You can [read it here &rarr;](https://wiki.php.net/rfc/generics)

### Creating Custom Generics

A generic consists of a proxied template class and the typed generic that extends
the base abstract generic. The proxied template class can make basic type assumptions
based on its behavior but it is untyped with respect to its parameters. This allows
for the proxied class to encapsulate the behavioral logic in the form of the template
while remaining ignorant of the proxy that wraps the class. The template class implements
the `ArtisanSDK\Generic\Contract` which requires a `generic()` static factory
be implemented. Application code should never depend directly on this template.

The typed generic extends the base abstract generic `ArtisanSDK\Generic\Generic`
which also implements the `ArtisanSDK\Generic\Contract`. The purpose of this class
is to allow application code to type hint a class that has the defined behavior
of the template while providing the templating functionality of a generic. This
maintains type consistency. The parent logic of the abstract generic uses reflection
when type checking is enabled (default) by comparing the templated parameters defined
in the doc blocks of the `generic()` method implemented on the typed generic against
those passed when a method on the proxied template class is called. A typed generic
must use the same parameter names in all methods of the template if the type of the
parameter is to be templated.

#### Stack Example

The following is an example of a custom `App\Types\Stack` generic which proxies
to the untyped `App\Types\Templates\Stack` class. Note that the `generic()` method on
both the typed generic and the proxied template have the same doc block (the
parameter name is important) and that all the public methods of the proxied
template class use the same parameter names in the doc blocks if the typed parameter
should be checked. The order of the typed parameters does not matter for custom
methods.

This is the typed generic which is what your application should type hint:

```php
<?php

namespace App\Types;

use ArtisanSDK\Generic\Generic;
use ArtisanSDK\Generic\Contract;

class Stack extends Generic
{
    public function __construct($item)
    {
        parent::__construct(Templates\Stack::class, $item);
    }

    /**
     * @param mixed $item for generic stack type
     */
    public static function generic() : Contract
    {
        $args = func_get_args();

        return new static(...$args);
    }
}
```

This is the proxied template class which defines the generics behavior. Notice
how `push()` type hints with the doc block the `$item` parameter and is therefore
type checked while `all()` and `pop()` accept no parameters and are not type checked.
Additionally `slice()` accepts parameters but does not type hint them and therefore
are not type checked via the proxy (though they are via PHP internals).

```php
<?php

namespace App\Types\Templates;

use ArtisanSDK\Generic\Contract;
use App\Types\Stack as Type;

class Stack implements Contract
{
    private $items = [];

    /**
     * @param mixed $item for generic stack type
     */
    public static function generic() : Contract
    {
        $args = func_get_args();

        return new Type(...$args);
    }

    public function all() : array
    {
        return $this->items;
    }

    /**
     * @param mixed $item to push on stack
     */
    public function push($item)
    {
        array_push($this->items, $item);

        return $this;
    }

    public function pop()
    {
        return array_pop($this->items);
    }

    public function slice(int $offset, int $length = null) : array
    {
        return array_slice($this->items, $offset, $length);
    }
}
```

### Run Without Type Checks

Set the environment variable `PHP_GENERICS_DISABLE=1` to disable generic type
checking (something you should do in production to improve speed so long as you
run checks in your CI/CD pipelines).

```bash
PHP_GENERICS_DISABLE=1 php -d memory_limit=256M tests/Example.php
```

The performance difference for generating 100K type objects is negligible at only
0.0141ms average while the cost difference of making 40K calls to the proxied
template class with type checking enabled is 20ms. The memory consumption is much
higher however with a 215MB difference observed.

## Licensing

Copyright (c) 2018 [Artisans Collaborative](https://artisanscollaborative.com)

This package is released under the MIT license. Please see the LICENSE file
distributed with every copy of the code for commercial licensing terms.
