<?php

// PHP_GENERICS_DISABLE=1 php -d memory_limit=256M tests/Example.php
// PHP_GENERICS_DISABLE=0 php -d memory_limit=256M tests/Example.php

require_once(__DIR__.'/../vendor/autoload.php');

use ArtisanSDK\Generic\Types\Collection;
use ArtisanSDK\Generic\Types\HashMap;

class Foo {}
class Bar {}

$iterations = 10; // number of performance interations to test
$instances = 100000; // number of instances to create per iteration

// Construct a typed generic different ways:
// 1) use the type factory
// 2) use the type constructor
// 3) use the template factory (bad)
$generic = HashMap::generic(HashMap::TYPE_STRING, Bar::class);
$generic = new HashMap(HashMap::TYPE_STRING, Bar::class);
$generic = ArtisanSDK\Generic\Types\Templates\HashMap::generic(HashMap::TYPE_STRING, Bar::class);

// Set the bar at the foo key in the hash map
$bars = HashMap::generic(HashMap::TYPE_STRING, Bar::class);
$bars->set('foo', new Bar()); // ['foo' => Bar]

// Demonstrates that type checking is based on reflected param names
$bar = new Bar();
$bars->set('bar', $bar);
$bars->get('bar'); // Bar
$bars->key($bar); // bar
$bars->all(); // ['foo' => Bar, 'bar' => Bar]

// Setting a foo is not allowed in a bar hash map
// Expecting Bar type argument but received Foo instead.
try { $bars->set('foo', new Foo()); } catch (InvalidArgumentException $e) { echo $e->getMessage().PHP_EOL;}

// Setting an integer key is not allowed in a string key hash map
// Expecting string type argument but received integer instead.
try { $bars->set(0, new Bar()); } catch (InvalidArgumentException $e) { echo $e->getMessage().PHP_EOL;}

// Demonstrate different generics can be created
$foos = HashMap::generic(HashMap::TYPE_INT, Foo::class);
$foos->set(0, new Foo());

// Different generics can have different signatures and still type check
$bars = Collection::generic(Bar::class);
$bars->add(new Bar());

// Adding a foo to a bar collection will fail
// Expecting Bar type argument but received Foo instead.
try { $bars->add(new Foo()); } catch (InvalidArgumentException $e) { echo $e->getMessage().PHP_EOL;}

// Both bars and foos are instances of collection generic.
// Typehint for the typed collection generic not the proxied collection generic
// nor the generic contract itself. Unfortunately you can't typehint for a bar
// typed collection or a foo type collection like you could with built in generics.
$foos = Collection::generic(Foo::class);
$bars = Collection::generic(Bar::class);
$callable = function(Collection $collection) {};
$callable($foos);
$callable($bars);

// Performance test for the cost of constructing generics
$timers = [];
$generics = [];
for($i=0;$i<$instances;$i++) {
    $time = microtime(true);
    $generics[] = HashMap::generic(HashMap::TYPE_INT, new stdClass());
    $timer = (microtime(true) - $time);
    $timers[] = $timer;
}
echo '= '.round(array_sum($timers) / count($timers) * 1000, 4).' ms average'.PHP_EOL;
echo '= '.round(memory_get_usage()/1024/1024).'MB'.PHP_EOL;

// Performance test for the cost of type checking at call time
$timers = [];
$generic = HashMap::generic(HashMap::TYPE_INT, new stdClass());
for($i=0;$i<$iterations;$i++) {
    $time = microtime(true);
    for($x=0;$x<$instances;$x++) {
        $obj = new stdClass();
        $generic->set($x, $obj);
        $generic->get($x);
        $generic->key($obj);
        $generic->all();
    }
    $timer = (microtime(true) - $time);
    $timers[] = $timer;
    echo ($i+1).' > '.round($timer*1000).' ms'.PHP_EOL;
}
echo '= '.round(array_sum($timers) / count($timers) * 1000).' ms average'.PHP_EOL;
echo '= '.round(memory_get_usage()/1024/1024).'MB'.PHP_EOL;
