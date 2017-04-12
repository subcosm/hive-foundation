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

use Subcosm\Hive\Container\HiveNode;
use Subcosm\Hive\Container\HiveObservationContainer;
use PHPUnit\Framework\TestCase;

class HiveObservationContainerTest extends TestCase
{
    /**
     * @test
     */
    public function instanceTest()
    {
        $node = new HiveNode();
        $instance = new HiveObservationContainer($node, $node::GET_STAGE);

        $data = ['foo' => 'bar'];

        $instance->withContextData($data);

        $this->assertSame($data, $instance->getContextData());
    }
}
