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


use Subcosm\Hive\HiveInterface;
use Subcosm\Hive\LoaderInterface;

abstract class AbstractLoader implements LoaderInterface
{
    /**
     * @var array
     */
    protected $data = [];

    /**
     * injects previously loaded contents into a node.
     *
     * @param HiveInterface $node
     * @return void
     */
    public function injectInto(HiveInterface $node): void
    {
        $this->inject($node, $this->data);
    }

    /**
     * returns the value-as-is identifier that prevents arrays being parsed as nodes and closures automatically secured.
     *
     * @return string
     */
    public function getValueAsIsIdentifier(): string
    {
        return '!';
    }

    /**
     * recursive injector.
     *
     * @param HiveInterface $node
     * @param array $data
     */
    protected function inject(HiveInterface $node, array $data)
    {
        foreach ( $data as $key => $value ) {
            $key = $this->detachRoot($key, $node->getRootIdentifier());

            if ( is_array($value) && ! $this->hasValueAsIsIdentifier($key) ) {
                $subNode = $node->node($key, true);
                $this->inject($subNode, $value);
            }
            else {
                $node->set($this->detachValueAsIs($key), $value);
            }
        }
    }

    /**
     * removes the root Identifier from a key.
     *
     * @param string $key
     * @param string $rootIdentifier
     * @return string
     */
    protected function detachRoot(string $key, string $rootIdentifier): string
    {
        return str_replace($rootIdentifier, '', $key);
    }

    /**
     * removes the "value-as-is" identifier from the beginning of a string.
     *
     * @param string $key
     * @return string
     */
    protected function detachValueAsIs(string $key): string
    {
        if ( $this->hasValueAsIsIdentifier($key) ) {
            return substr($key, strlen($this->getValueAsIsIdentifier()));
        }

        return $key;
    }

    /**
     * checks whether a value should be treated as "value-as-is".
     *
     * @param string $key
     * @return bool
     */
    protected function hasValueAsIsIdentifier(string $key): bool
    {
        return 0 === strpos($key, $this->getValueAsIsIdentifier());
    }
}
