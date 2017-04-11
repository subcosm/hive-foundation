<?php
/**
 * This file is part of the subcosm.
 *
 * (c)2017 Matthias Kaschubowski
 *
 * This code is licensed under the MIT license,
 * a copy of the license is stored at the project root.
 */

namespace Subcosm\Hive\Traits;


use Subcosm\Hive\HiveInterface;

trait HierarchyNegotiationTrait
{
    /**
     * @var HiveInterface[]
     */
    protected $nodes = [];

    /**
     * marshals the key for a node.
     *
     * @param string $key
     * @return string
     */
    protected function marshalNodeKey(string $key)
    {
        $key = strtolower($key);

        if ( 0 === strpos($key, $this->getRootIdentifier()) ) {
            return $this->getRootIdentifier().trim($key, $this->getQueryDivider().' ');
        }

        return trim($key, $this->getQueryDivider().' ');
    }

    /**
     * marshals a new instance of the origin.
     *
     * @param HiveInterface $origin
     * @return HiveInterface
     */
    protected function marshalNodeInstance(HiveInterface $origin): HiveInterface
    {
        return new $origin;
    }

    /**
     * ensures the provided entity path, creates not existing nodes when $createIfNotExists is set to true.
     *
     * @param string $entity
     * @param bool $createIfNotExists
     * @return null|HiveInterface
     */
    public function node(string $entity, bool $createIfNotExists = false): ? HiveInterface
    {
        $query = $this->marshalNodeKey($entity);

        $query = 0 === strpos($query, $this->getRootIdentifier())
            ? substr($query, strlen($this->getRootIdentifier()) - 1)
            : $query
        ;

        if ( 0 === strpos($query, $this->getRootIdentifier()) ) {
            return $this->getRoot()->node($query);
        }

        if ( false !== strpos($query, $this->getQueryDivider()) && array_key_exists($query, $this->nodes) ) {
            return $this->nodes[$query];
        }

        if (
            false !== strpos($query, $this->getQueryDivider()) &&
            ! array_key_exists($query, $this->nodes) &&
            $createIfNotExists
        ) {
            /** @var HiveInterface $this */
            return $this->nodes[$query] = $this->marshalNodeInstance($this);
        }

        if (
            false !== strpos($query, $this->getQueryDivider()) &&
            ! array_key_exists($query, $this->nodes) &&
            ! $createIfNotExists
        ) {
            return null;
        }

        $current = strstr($query, $this->getQueryDivider(), true);

        $forwardedQuery = ltrim(
            strstr($query, $this->getQueryDivider(), false),
                $this->getQueryDivider()
        );

        if ( ! array_key_exists($current, $this->nodes) && $createIfNotExists ) {
            /** @var HiveInterface $this */
            return $this->nodes[$query] = $this->marshalNodeInstance($this)->node($forwardedQuery);
        }

        if ( ! array_key_exists($current, $this->nodes) && ! $createIfNotExists ) {
            return null;
        }

        return $this->nodes[$query]->node($forwardedQuery);
    }

    /**
     * returns the root node. When this node is the highest node in the hierarchy, the current
     * node will be returned.
     *
     * @return HiveInterface
     */
    abstract public function getRoot(): HiveInterface;

    /**
     * returns the node dividing character for queries provided as entity keys.
     *
     * @return string
     */
    abstract public function getQueryDivider(): string;

    /**
     * returns the root reference identifier for queries provided as entity keys.
     *
     * @return string
     */
    abstract public function getRootIdentifier(): string;
}
