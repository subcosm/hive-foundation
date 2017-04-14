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
use PHPUnit\Framework\TestCase;
use Subcosm\Hive\Loader\ArrayLoader;
use Subcosm\Hive\Loader\JsonLoader;

class AbstractLoaderTest extends TestCase
{
    protected $pathNameFile;
    protected $baseNameFile;
    protected $data = [];

    public function setUp()
    {
        $this->pathNameFile = __DIR__.'/Files/test.json';
        $this->baseNameFile = __DIR__.'/Files/test';
    }

    /**
     * @test
     */
    public function loadJsonTest()
    {
        $loader = new JsonLoader();
        $loader->load($this->pathNameFile);

        $node = new HiveNode();
        $loader->injectInto($node);

        $this->assertSame('bar', $node->get('foo'));
        $this->assertSame(['baz' => ['boing' => []]], $node->get('foo.baz'));
    }

    /**
     * @test
     */
    public function loadJsonWithoutExtensionTest()
    {
        $loader = new JsonLoader();
        $loader->load($this->baseNameFile);

        $node = new HiveNode();
        $loader->injectInto($node);

        $this->assertSame('bar', $node->get('foo'));
        $this->assertSame(['baz' => ['boing' => []]], $node->get('foo.baz'));
    }

    /**
     * @test
     */
    public function loadJsonByFileInfoObjectTest()
    {
        $loader = new JsonLoader();
        $loader->load(new \SplFileInfo($this->pathNameFile));

        $node = new HiveNode();
        $loader->injectInto($node);

        $this->assertSame('bar', $node->get('foo'));
        $this->assertSame(['baz' => ['boing' => []]], $node->get('foo.baz'));
    }

    /**
     * @test
     * @expectedException  \Subcosm\Hive\Exception\LoaderException
     */
    public function loadJsonFailureTest()
    {
        $loader = new JsonLoader();
        $loader->load(true);
    }

    /**
     * @test
     */
    public function loadArrayTest()
    {
        $loader = new ArrayLoader();
        $loader->load([
            'foo' => 'bar',
        ]);

        $node = new HiveNode();
        $loader->injectInto($node);

        $this->assertSame('bar', $node->get('foo'));
    }

    /**
     * @test
     * @expectedException  \Subcosm\Hive\Exception\LoaderException
     */
    public function loadArrayFailureTest()
    {
        $loader = new ArrayLoader();
        $loader->load((object)['foo' => 'bar']);
    }
}
