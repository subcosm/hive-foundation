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

class JsonLoader extends AbstractLoader implements LoaderInterface
{
    /**
     * loads data from the provided resource.
     *
     * @param mixed $resource
     * @throws LoaderException when the provided resource is invalid
     * @return LoaderInterface
     */
    public function load($resource): LoaderInterface
    {
        if ( is_string($resource) && is_file($resource) ) {
            $this->data = json_decode(file_get_contents($resource), true);

            return $this;
        }

        if ( is_string($resource) && is_file($resource.'.json') ) {
            $this->data = json_decode(file_get_contents($resource.'.json'), true);

            return $this;
        }

        if ( $resource instanceof \SplFileInfo ) {
            $this->data = json_decode(file_get_contents($resource->getPathname()), true);

            return $this;
        }

        throw new LoaderException('The resource for this loaded must be a string or SplFileInfo object');
    }

}
