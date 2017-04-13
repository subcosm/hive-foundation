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


use Subcosm\Hive\Exception\HiveException;
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
     * @var callable|null
     */
    protected $defaultDeclaration;

    /**
     * HiveIdentity constructor.
     * @param HiveInterface $node
     * @param string $name
     * @param callable|null $defaultDeclaration
     * @throws HiveException when the $name parameter results into a empty string
     */
    public function __construct(HiveInterface $node, string $name, callable $defaultDeclaration = null)
    {
        if ( empty(trim($name)) ) {
            throw new HiveException('Hive node name can not be empty');
        }

        $this->node = $node;
        $this->name = trim($name);
        $this->defaultDeclaration = $defaultDeclaration;
    }

    /**
     * returns the parent node.
     *
     * @return HiveInterface
     */
    public function getParentNode(): HiveInterface
    {
        return $this->node;
    }

    /**
     * returns the name of the node.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * returns the default declaration value.
     *
     * @return callable|null
     */
    public function getDefaultDeclaration(): ? callable
    {
        return $this->defaultDeclaration;
    }

}
