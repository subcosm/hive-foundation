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

use Subcosm\Hive\Container\DeclarativeHiveNode;
use PHPUnit\Framework\TestCase;
use Subcosm\Tests\Hive\Mocks\MockedObserver;

class DeclarativeHiveNodeTest extends TestCase
{
    /**
     * @test
     */
    public function declarationTest()
    {
        $node = new DeclarativeHiveNode();

        $status = false;

        $node->entity('foo', function($value) use(&$status) {
            if ( $value instanceof \Closure ) {
                $status = true;
            }
        });

        $node->set('foo', $node->secure(function() {}));

        $this->assertTrue($status);

        $status = false;

        $node->entity('~foo', function($value) use(&$status) {
            if ( $value instanceof \Closure ) {
                $status = true;
            }
        });

        $node->set('foo', $node->secure(function() {}));

        $status = false;

        $node->entity('~foo.bar.baz', function($value) use(&$status) {
            if ( $value instanceof \Closure ) {
                $status = true;
            }
        });

        $node->set('foo.bar.baz', $node->secure(function() {}));

        $status = false;

        $node->entity('~foo.baz', function($value) use(&$status) {
            if ( $value instanceof \Closure ) {
                $status = true;
            }
        });

        $node->set('foo.baz', $node->secure(function() {}));
    }

    /**
     * @test
     */
    public function mockedObserverDeclarationTest()
    {
        $node = new DeclarativeHiveNode();
        $node->attach($observer = new MockedObserver());

        $status = false;

        $node->entity('cool', function($value) use (&$status) {
            if ( is_string($value) ) {
                $status = true;
            }
        });

        $node->set('cool', 'item');

        $this->assertTrue($status);
    }

    /**
     * @test
     * @expectedException \Subcosm\Hive\Exception\UnknownEntityException
     */
    public function declareEmptyEntityString()
    {
        $node = new DeclarativeHiveNode();
        $node->entity('', function() {});
    }

    /**
     * @test
     */
    public function defaultDeclarationTest()
    {
       $node = new DeclarativeHiveNode();

       $status = false;

       $node->defaultEntity(function($value) use(&$status) {
           if ( is_string($value) ) {
               $status = true;
           }

           return $value;
       });

       $node->set('foo', 'bar');

       $this->assertTrue($status);

       $status = false;

       $node->set('bar', 'baz');

       $this->assertTrue($status);

       $status = false;

       $node->set('baz.foo.bar', 'boing');

       $this->assertTrue($status);
    }
}
