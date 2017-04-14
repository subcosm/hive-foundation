# hive-foundation
Hive Foundation is Container Components Foundation the delivers
feature-rich hierarchical Key-Value node-based containers with
an omni-present query interface.

General Status:  
[![Build Status](https://travis-ci.org/subcosm/hive-foundation.svg?branch=master)](https://travis-ci.org/subcosm/hive-foundation)
[![codecov](https://codecov.io/gh/subcosm/hive-foundation/branch/master/graph/badge.svg)](https://codecov.io/gh/subcosm/hive-foundation)
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/1f9a21c7-7c8a-4c0e-9904-ed32081367f1/mini.png)](https://insight.sensiolabs.com/projects/1f9a21c7-7c8a-4c0e-9904-ed32081367f1)
[![Code Climate](https://img.shields.io/codeclimate/github/subcosm/hive-foundation.svg)](https://codeclimate.com/github/subcosm/hive-foundation)
[![Gittip](http://img.shields.io/gittip/subcosm.svg)](https://gittip.com/subcosm/)

Integrity and Usage:  
![Downloads](https://img.shields.io/github/downloads/subcosm/hive-foundation/total.svg)
[![Latest](https://img.shields.io/packagist/v/subcosm/hive-foundation.svg)](https://packagist.org/packages/subcosm/hive-foundation)

### Dependencies

- [psr/container](https://packagist.org/packages/psr/container)
- [subcosm/observatory](https://packagist.org/packages/subcosm/observatory)
- PHP 7.1 or higher.

Optionally:  
- `ext/spl` if do want to use `SplFileObject`'s for loaders.

### What is a hive node?

A hive node implements one leaf of a hierarchy where you may set
values to the node itself, child nodes or the root node with simple
commands.

### How do i...

The following examples explain how hive nodes in general do work.

##### ... create a root node?

Root nodes are the top-level node of any node hierarchy, there can
be only one root node but as many child nodes as you need. Root nodes
are created like this:

```php
use Subcosm\Hive\Container\HiveNode;

$root = new HiveNode();
```

##### ... append child nodes?

Hive nodes are self-extending, you may create nodes by yourself by
calling `HiveNode::node($nodeName, true)`.

##### ... append values to a node?

Like this:

```php
use Subcosm\Hive\Container\HiveNode;

$root = new HiveNode();

$root->set('foo', 'bar');
```

##### ... append lazy loading for values?

Hive nodes do see closures as lazy-load values, just wrap what you
want to be lazy loaded into a closure.

```php
use Subcosm\Hive\Container\HiveNode;

$root = new HiveNode();

$root->set('foo', function() {
    return 'Hello World';
});
```

##### ... get a value from a node?

Like this:

```php
use Subcosm\Hive\Container\HiveNode;

$root = new HiveNode();

$root->set('foo', function() {
    return 'Hello World';
});

echo $root->get('foo'); // = Hello World
```

##### ... set or get values hierarchically?

Hive nodes implement a hierarchy-safe query mechanism to get and
store values into the hierarchy.

```php
use Subcosm\Hive\Container\HiveNode;

$root = new HiveNode();

$root->set('router', function() use ($root) {
    return $root->get('router.factory')->factorize();
});

$root->set('router.factory', function() {
    return new RouterFactory();
});

$router = $root->get('router');
```

In the first set instruction, the key `router` of the root node will
receive the closure. The second set instruction will (automatically)
create the node 'router' (not value-key), and the key `factory` at
the `router`-node who will receive the closure.

You can also access the root node from sub nodes:

```php
use Subcosm\Hive\Container\HiveNode;

$root = new HiveNode();

$root->set('logger', function() {
    return new Monolog\Logger();
});

$routing = $root->node('router', true);

$root->set('router', function() use ($routing) {
    return $routing->get('factory')->factorize();
});

$routing->set('factory', function() use ($routing) {
    return new RouteFactory($routing->get('~logger'));
});

$router = $root->get('router');

// or

$router = $routing->get('~router');
```

### Is there any hook or event mechanism?

Yes and no. You can not manipulate values from outside the hive
structure using events, but you can observe a hive node and issue
events in your event dispatcher of your choice using the `Observatory`-
Implementation of each hive node.

The available observer stages can be found at the
`HiveInterface` and `DeclarationAwareInterface` represented by
`*_STAGE` constants.

`Observatory`-Observers are shared across the entire hive structure and
can be altered to own observer queues from each parent independently.

### Can i assign nodes manually?

No. Whenever you assign items to a container they will remain items as
they were set (a closure is the only exception here). You have to use
`HiveNode::node($name, true)` to create new child-nodes, or you just
let the hive node create them for you. The inability to set nodes
manually was decided because of avoiding conflicts of query divider
and root definition tokens across node structures. Integrity first.

### Whats about value validation?

The `HiveNode` itself does not provided an interface to validate values.
`DeclarativeHiveNode` provides an interface to declare a validator on a
per item level or a default validator for all items and sub nodes.

The provided callback receives the value to set as the first parameter
and requires to return the value to be set to the hive node, otherwise the
value will default to null.

```php
use Subcosm\Hive\Container\DeclarativeHiveNode;

$node = new DeclarativeHiveNode();

$node->entity('foo', function($value) {
    if ( ! is_string($value) ) {
        throw new InvalidArgumentException('Value must be string for foo');
    }
    
    return $value;
});

$node->set('foo', 12345); // throws the exception
$node->set('foo', '12345'); // sets the value
```

```php
use Subcosm\Hive\Container\DeclarativeHiveNode;

$node = new DeclarativeHiveNode();

$node->defaultEntity(function($value) {
    return is_array($value) || is_object($value) ? json_encode($value) : (string) $value;
});

$node->set('foo', 12345); // foo => '12345'
$node->set('foo', ['foo' => 'bar']); // foo => {"foo":"bar"}
```

### Import data to nodes with loaders

```php
use Subcosm\Hive\{
    Container\HiveNode,
    Loader\ArrayLoader
};

$node = new HiveNode();
$loader = new ArrayLoader();
$loader->load([
    'foo.bar' => 'baz'
]);

$loader->injectInto($node);

echo $node->get('foo.bar'); // => "baz"
```

### Package Stability and Maintainers

This package is considered stable. The maintainers of this package are:

- [Matthias Kaschubowski](https://github.com/nhlm)

### License

This package is licensed under the [MIT-License](LICENSE).
