<?php
/**
 * This file is part of the subcosm.
 *
 * (c)2017 Matthias Kaschubowski
 *
 * This code is licensed under the MIT license,
 * a copy of the license is stored at the project root.
 */

namespace Subcosm\Hive\Loader;


use Subcosm\Hive\Exception\LoaderException;
use Subcosm\Hive\LoaderInterface;

class ArrayLoader extends AbstractLoader implements LoaderInterface
{
    /**
     * loads data from the provided resource.
     *
     * @param array $resource
     * @throws LoaderException when the provided resource is invalid
     * @return LoaderInterface
     */
    public function load($resource): LoaderInterface
    {
        if ( ! is_array($resource) ) {
            throw new LoaderException('Provided resource is not an array');
        }

        $this->data = $resource;

        return $this;
    }
}
