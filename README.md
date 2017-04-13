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

### Package Stability and Maintainers

This package is considered stable. The maintainers of this package are:

- [Matthias Kaschubowski](https://github.com/nhlm)

### License

This package is licensed under the [MIT-License](LICENSE).
