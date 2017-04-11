<?php
/**
 * This file is part of the subcosm.
 *
 * (c)2017 Matthias Kaschubowski
 *
 * This code is licensed under the MIT license,
 * a copy of the license is stored at the project root.
 */

namespace Subcosm\Hive\Container;


use Subcosm\Hive\HiveIdentityInterface;
use Subcosm\Hive\HiveInterface;

/**
 * Class HiveIdentity
 * @package Subcosm\Hive\Container
 */
class HiveIdentity implements HiveIdentityInterface
{
    /**
     * @var HiveInterface
     */
    protected $node;
    /**
     * @var string
     */
    protected $name;

    /**
     * HiveIdentity constructor.
     * @param HiveInterface $node
     * @param string $name
     */
    public function __construct(HiveInterface $node, string $name)
    {
        $this->node = $node;
        $this->name = $name;
    }

    /**
     * returns the parent node.
     *
     * @return HiveInterface
     */
    public function getParentNode(): HiveInterface
    {
        // TODO: Implement getParentNode() method.
    }

    /**
     * returns the name of the node.
     *
     * @return string
     */
    public function getName(): string
    {
        // TODO: Implement getName() method.
    }

}