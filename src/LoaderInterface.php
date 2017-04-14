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


use Subcosm\Hive\Exception\LoaderException;

interface LoaderInterface
{
    /**
     * loads data from the provided resource.
     *
     * @param mixed $resource
     * @throws LoaderException when the provided resource is invalid
     * @return LoaderInterface
     */
    public function load($resource): LoaderInterface;

    /**
     * injects previously loaded contents into a node.
     *
     * @param HiveInterface $node
     * @return void
     */
    public function injectInto(HiveInterface $node): void;

    /**
     * returns the value-as-is identifier that prevents arrays being parsed as nodes and closures automatically secured.
     *
     * @return string
     */
    public function getValueAsIsIdentifier(): string;
}
