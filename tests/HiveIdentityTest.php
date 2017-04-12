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

class HiveIdentityTest extends TestCase
{
    /**
     * @test
     */
    public function instanceTest()
    {
        $node = new HiveNode();
        $instance = new HiveIdentity($node, $name = 'test');

        $this->assertSame($node, $instance->getParentNode());
        $this->assertSame($name, $instance->getName());
    }

    /**
     * @test
     * @expectedException \Subcosm\Hive\Exception\HiveException
     */
    public function instanceFailedTest()
    {
        $node = new HiveNode();
        $instance = new HiveIdentity($node, '   ');
    }
}
