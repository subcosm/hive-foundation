<?php
/**
 * This file is part of the subcosm.
 *
 * (c)2017 Matthias Kaschubowski
 *
 * This code is licensed under the MIT license,
 * a copy of the license is stored at the project root.
 */

namespace Subcosm\Tests\Hive;

use Subcosm\Hive\Container\HiveIdentity;
use Subcosm\Hive\Container\HiveNode;
use PHPUnit\Framework\TestCase;
use Subcosm\Tests\Hive\Mocks\MockedObserver;

class HiveNodeTest extends TestCase
{
    /**
     * @var HiveNode
     */
    protected $instance;

    public function setUp()
    {
        $this->instance = new HiveNode();
    }

    /**
     * @test
     */
    public function instanceTest()
    {
        $instance = new HiveNode();
        $instanceInjected = new HiveNode(new HiveIdentity($instance, 'test'), $instance->getObservers());
        $instanceUnknownObserver = new HiveNode(new HiveIdentity($instance, 'test'));

        $this->assertSame($instance, $instanceInjected->getParent());
        $this->assertSame($instance, $instanceUnknownObserver->getParent());
        $this->assertSame($instance, $instanceInjected->getRoot());
        $this->assertSame($instance, $instanceUnknownObserver->getRoot());

        $this->assertTrue($instanceInjected->isRoot($instance));
        $this->assertTrue($instanceUnknownObserver->isRoot($instance));
        $this->assertTrue($instance->isRoot());
        $this->assertFalse($instanceInjected->isRoot());
        $this->assertFalse($instanceUnknownObserver->isRoot());

        $this->assertTrue($instanceInjected->hasParent());
        $this->assertTrue($instanceUnknownObserver->hasParent());
        $this->assertFalse($instance->hasParent());

        $this->assertTrue($instanceInjected->isParent($instance));
        $this->assertTrue($instanceUnknownObserver->isParent($instance));
        $this->assertFalse($instance->isParent($instanceUnknownObserver));
        $this->assertFalse($instance->isParent($instanceInjected));
        $this->assertFalse($instance->isParent($instance));
        $this->assertFalse($instanceInjected->isParent($instanceInjected));
        $this->assertFalse($instanceUnknownObserver->isParent($instanceUnknownObserver));

        $this->assertSame('.', $instance->getQueryDivider());
        $this->assertSame('~', $instance->getRootIdentifier());

        $this->assertNull($instance->getName());
        $this->assertSame('test', $instanceUnknownObserver->getName());
        $this->assertSame('test', $instanceInjected->getName());

        $this->assertSame('test', $instanceInjected->getPath());
        $this->assertSame('test', $instanceUnknownObserver->getPath());
    }

    /**
     * @test
     */
    public function nodeTest()
    {
        $node = new HiveNode();

        $instance = $node->node('foo', true);
        $noCopy = $node->node('foo');
        $noCopyAtAll = $node->node('~foo');

        $this->assertSame($instance, $noCopy);
        $this->assertSame($instance, $noCopyAtAll);

        $unknown = $node->node('unknown');
        $unknownAtRoot = $node->node('~unknown');

        $unknownHierarchy = $node->node('unknown.really.do.not.know');
        $unknownHierarchyRoot = $node->node('~unknown.really.do.not.know');

        $this->assertNull($unknown);
        $this->assertNull($unknownAtRoot);
        $this->assertNull($unknownHierarchy);
        $this->assertNull($unknownHierarchyRoot);

        $instanceHierarchy = $node->node('foo.bar.baz', true);
        $instanceHierarchyGet = $node->node('foo.bar.baz');

        $this->assertSame($instanceHierarchy, $instanceHierarchyGet);
    }

    /**
     * @test
     * @expectedException \Subcosm\Hive\Exception\UnknownEntityException
     */
    public function emptyNodeNameFailureTest()
    {
        $node = new HiveNode();

        $node->node('');
    }

    /**
     * @test
     */
    public function setAndGetTest()
    {
       $this->instance->set('foo', 'test');
       $this->instance->set('bar.baz', 'test 2');
       $this->instance->set('baz.boing.bing', 'test 3');

       $this->assertSame('test', $this->instance->get('foo'));
       $this->assertSame('test 2', $this->instance->get('bar.baz'));
       $this->assertSame('test 3', $this->instance->get('baz.boing.bing'));

       $testNode = $this->instance->node('baz.boing');

       $this->assertInstanceOf(HiveNode::class, $testNode);

       $testNode->set('~lol.rofl', 'test 4');

       $this->assertSame('test 4', $this->instance->get('lol.rofl'));
       $this->assertSame('test 4', $testNode->get('~lol.rofl'));
    }

    /**
     * @test
     * @expectedException \Subcosm\Hive\Exception\UnknownEntityException
     */
    public function getUnknownFailTest()
    {
        $this->instance->get('unknown');
    }

    /**
     * @test
     * @expectedException \Subcosm\Hive\Exception\UnknownEntityException
     */
    public function getUnknownHierarchicallyFailTest()
    {
        $this->instance->get('unknown.item');
    }

    /**
     * @test
     * @expectedException \Subcosm\Hive\Exception\UnknownEntityException
     */
    public function getEmptyEntityStringTest()
    {
        $this->instance->get('   ');
    }

    /**
     * @test
     * @expectedException \Subcosm\Hive\Exception\UnknownEntityException
     */
    public function getRootOnlyStringTest()
    {
        $this->instance->get('~');
    }

    /**
     * @test
     */
    public function mockedObserverTest()
    {
        $node = new HiveNode();
        $node->attach($observer = new MockedObserver());

        $node->set('cool', 'item');

        $this->assertTrue($node->has('cool'));

        $this->assertFalse($observer->primitiveStatus);

        $node->get('cool');

        $this->assertTrue($observer->primitiveStatus);

        $node->detach($observer);
    }

    /**
     * @test
     * @expectedException \Subcosm\Hive\Exception\UnknownEntityException
     */
    public function setEmptyEntityStringTest()
    {
        $base = new HiveNode();
        $base->set('', '');
    }

    /**
     * @test
     */
    public function hasTest()
    {
        $base = new HiveNode();
        $node = $base->node('foo', true);

        $base->set('find-me', 'something');

        $this->assertTrue($base->has('find-me'));
        $this->assertTrue($node->has('~find-me'));
    }

    /**
     * @test
     * @expectedException \Subcosm\Hive\Exception\UnknownEntityException
     */
    public function hasEmptyEntityFailedTest()
    {
        $base = new HiveNode();
        $base->has('');
    }

    /**
     * @test
     */
    public function hasHierarchyTest()
    {
        $base = new HiveNode();
        $base->set('foo.bar.baz.bing', 'something');

        $this->assertTrue($base->has('foo.bar.baz.bing'));
        $this->assertTrue($base->node('foo.bar')->has('baz.bing'));
    }

    /**
     * @test
     */
    public function closureTest()
    {
        $base = new HiveNode();

        $base->set('foo', function() {
           return 'bar';
        });

        $base->set('bar', $base->secure(function() {

        }));

        $this->assertSame('bar', $base->get('foo'));
        $this->assertInstanceOf(\Closure::class, $base->get('bar'));
    }
}
