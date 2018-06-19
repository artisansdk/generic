<?php

// PHP_GENERICS_DISABLE=1 php -d memory_limit=256M tests/Example.php
// PHP_GENERICS_DISABLE=0 php -d memory_limit=256M tests/Example.php

require_once(__DIR__.'/../vendor/autoload.php');

use ArtisanSDK\Generic\Generic;
use ArtisanSDK\Generic\Contract;
use ArtisanSDK\Generic\Types\Collection;
use ArtisanSDK\Generic\Types\HashMap;

class Duck {}
class User {}

// Construct a typed generic different ways:
// 1) use the type factory
// 2) use the type constructor
// 3) use the concrete factory (bad)
$generic = HashMap::generic(Contract::TYPE_STRING, User::class);
$generic = new HashMap(Contract::TYPE_STRING, User::class);
$generic = ArtisanSDK\Generic\Concretes\HashMap::generic(Contract::TYPE_STRING, User::class);

// Set the user at the foo key in the hash map
$users = HashMap::generic(Contract::TYPE_STRING, User::class);
$users->set('foo', new User()); // ['foo' => User]

// Demonstrates that type checking is based on reflected param names
$user = new User();
$users->set('bar', $user);
$users->get('bar'); // User
$users->key($user); // bar
$users->all(); // ['foo' => User, 'bar' => User]

// Setting a duck is not allowed in a user hash map
// Expecting User type argument but received Duck instead.
try { $users->set('foo', new Duck()); } catch (InvalidArgumentException $e) { echo $e->getMessage().PHP_EOL;}

// Setting an integer key is not allowed in a string key hash map
// Expecting string type argument but received integer instead.
try { $users->set(0, new User()); } catch (InvalidArgumentException $e) { echo $e->getMessage().PHP_EOL;}

// Demonstrate different generics can be created
$ducks = HashMap::generic(Contract::TYPE_INT, Duck::class);
$ducks->set(0, new Duck());

// Different generics can have different signatures and still type check
$users = Collection::generic(User::class);
$users->add(new User());

// Adding a duck to a user collection will fail
// Expecting User type argument but received Duck instead.
try { $users->add(new Duck()); } catch (InvalidArgumentException $e) { echo $e->getMessage().PHP_EOL;}

// Both users and ducks are instances of collection generic.
// Typehint for the typed collection generic not the proxied collection generic
// nor the generic contract itself. Unfortunately you can't typehint for a user
// typed collection or a duck type collection like you could with built in generics.
$users = Collection::generic(User::class);
$ducks = Collection::generic(Duck::class);
$foo = function(Collection $collection) {};
$foo($users);
$foo($ducks);

// Performance test for the cost of constructing generics
// 0.0141ms, 215MB difference
$timers = [];
$generics = [];
for($i=0;$i<100000;$i++) {
    $time = microtime(true);
    $generics[] = HashMap::generic(Generic::TYPE_INT, new stdClass());
    $timer = (microtime(true) - $time);
    $timers[] = $timer;
}
print_r('= '.round(array_sum($timers) / count($timers) * 1000, 4).' ms average'.PHP_EOL);
print_r('= '.round(memory_get_usage()/1024/1024).'MB'.PHP_EOL);

// Performance test for the cost of type checking at call time
// 20ms, 215MB difference
$timers = [];
$generic = HashMap::generic(Generic::TYPE_INT, new stdClass());
for($i=0;$i<10;$i++) {
    $time = microtime(true);
    for($x=0;$x<10000;$x++) {
        $obj = new stdClass();
        $generic->set($x, $obj);
        $generic->get($x);
        $generic->key($obj);
        $generic->all();
    }
    $timer = (microtime(true) - $time);
    $timers[] = $timer;
    print_r(($i+1).' > '.round($timer*1000).' ms'.PHP_EOL);
}
print_r('= '.round(array_sum($timers) / count($timers) * 1000).' ms average'.PHP_EOL);
print_r('= '.round(memory_get_usage()/1024/1024).'MB'.PHP_EOL);
