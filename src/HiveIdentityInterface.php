<?php
/**
 * This file is part of the subcosm.
 *
 * (c)2017 Matthias Kaschubowski
 *
 * This code is licensed under the MIT license,
 * a copy of the license is stored at the project root.
 */

namespace Subcosm\Hive;


interface HiveIdentityInterface
{
    /**
     * returns the parent node.
     *
     * @return HiveInterface
     */
    public function getParentNode(): HiveInterface;

    /**
     * returns the name of the node.
     *
     * @return string
     */
    public function getName(): string;
}