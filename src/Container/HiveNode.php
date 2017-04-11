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


use Subcosm\Hive\Exception\IncompatibleInstanceException;
use Subcosm\Hive\Exception\UnknownEntityException;
use Subcosm\Hive\HiveIdentityInterface;
use Subcosm\Hive\HiveInterface;
use Subcosm\Hive\Traits\HierarchyNegotiationTrait;

class HiveNode implements HiveInterface
{
    use HierarchyNegotiationTrait;

    /**
     * @var null|HiveInterface
     */
    protected $parent;

    /**
     * @var null|string
     */
    protected $name;

    /**
     * @var mixed[]
     */
    protected $values = [];

    /**
     * HiveNode constructor.
     * @param HiveIdentityInterface|null $parent
     */
    public function __construct(HiveIdentityInterface $parent = null)
    {
        $this->parent = $parent->getParentNode();
        $this->name = $parent->getName();
    }

    /**
     * gets the queried entity.
     *
     * @param string $entity
     * @throws UnknownEntityException
     * @return mixed
     */
    public function get($entity)
    {
        [$token, $query] = $this->marshalPartialQuery((string)$entity)['firstBank'];

        if ( ! array_key_exists($token, $this->values) ) {
            throw new UnknownEntityException('Entity not found: '.$token);
        }

        if (null === $query) {
            return $this->values[$token];
        }

        if ( ! array_key_exists($token, $this->nodes) ) {
            throw new UnknownEntityException('Node not found: '.$token);
        }

        try {
            return $this->nodes[$token]->get($query);
        }
        catch (UnknownEntityException $exception) {
            throw new UnknownEntityException('query path not found: '.$entity, 0, $exception);
        }
    }

    /**
     * Checks whether the provided entity is known or not.
     *
     * @param string $entity
     * @return bool
     */
    public function has($entity)
    {
        [$token, $query] = $this->marshalPartialQuery((string) $entity)['firstBank'];

        if ( null === $query ) {
            return array_key_exists($token, $this->values);
        }

        return array_key_exists($token, $this->nodes) ? $this->nodes[$token]->has($query) : false;
    }

    /**
     * sets the value of the provided entity.
     *
     * @param string $entity
     * @param $value
     * @throws IncompatibleInstanceException
     * @return mixed
     */
    public function set(string $entity, $value): void
    {
        if ( $value instanceof HiveInterface && ! is_a($value, $this->getMinimumClassLevel()) ) {
            throw new IncompatibleInstanceException(
                'The provided value must be instance or subclass of: '.$this->getMinimumClassLevel()
            );
        }

        $inspectionData = $this->marshalNodeQuery($entity);

        ['token' => $token, 'query' => $query, 'root' => $isRoot] = $inspectionData;

        if ( $isRoot ) {
            $this->getRoot()->set(join($this->getQueryDivider(), array_filter([$query, $token])), $value);
            return;
        }

        if ( $query !== null ) {
            $this->node($query, true)->set($token, $value);
            return;
        }

        $this->values[$token] = $this->cover($token, $value);
    }

    /**
     * checks if the current container is the root container. When $node is provided, the provided node
     * will be checked.
     *
     * @param HiveInterface|null $node
     * @return bool
     */
    public function isRoot(HiveInterface $node = null): bool
    {
        if ( $node instanceof HiveInterface ) {
            return $this->getRoot() === $node;
        }

        return $this->getRoot() === $this;
    }

    /**
     * returns the root node. When this node is the highest node in the hierarchy, the current
     * node will be returned.
     *
     * @return HiveInterface
     */
    public function getRoot(): HiveInterface
    {
        $instance = $this;

        while ( $current = $this->getParent() ) {
            $instance = $current;
        }

        return $instance;
    }

    /**
     * checks whether this container has a parent node or not.
     *
     * @return bool
     */
    public function hasParent(): bool
    {
        return $this->parent instanceof HiveInterface;
    }

    /**
     * checks if the provided node is the parent node of this node.
     *
     * @param HiveInterface $node
     * @return bool
     */
    public function isParent(HiveInterface $node): bool
    {
        return $this->parent === $node;
    }

    /**
     * returns the parent node or null if no parent node is given.
     *
     * @return null|HiveInterface
     */
    public function getParent(): ? HiveInterface
    {
        return $this->parent;
    }

    /**
     * returns the node dividing character for queries provided as entity keys.
     *
     * @return string
     */
    public function getQueryDivider(): string
    {
        return ".";
    }

    /**
     * returns the root reference identifier for queries provided as entity keys.
     *
     * @return string
     */
    public function getRootIdentifier(): string
    {
        return "~";
    }

    /**
     * returns the minimum class level accepted by set when nodes are about to set to a entity key.
     *
     * @return string
     */
    public function getMinimumClassLevel(): string
    {
        return get_called_class();
    }

    /**
     * returns the name of the node (or null when the current node is the root node).
     *
     * @return null|string
     */
    public function getName(): ? string
    {
        return $this->name;
    }

    /**
     * marshals the current path.
     *
     * @return null|string
     */
    public function getPath(): ? string
    {
        return $this->marshalCurrentPath();
    }

    /**
     * returns an inspection data array for the provided query, containing a root-flag, a token and the query part.
     *
     * @param string $query
     * @return array
     */
    protected function marshalNodeQuery(string $query): array
    {
        $normalizedQuery = $this->marshalNodeKey($query);

        $root = false;

        if ( 0 === strpos($normalizedQuery, $this->getRootIdentifier()) ) {
            $root = true;
            $normalizedQuery = substr($normalizedQuery, strlen($this->getRootIdentifier()) - 1);
        }

        $pattern = '~'.preg_quote($this->getQueryDivider()).'(?=[^'.$this->getQueryDivider().']*$)~';

        list($query, $token) = preg_split($pattern, $normalizedQuery);

        if ( $token === null ) {
            $token = $query;
            $query = null;
        }

        return compact('token', 'query', 'root');
    }

    /**
     * returns an inspection data array for the provided query, containing all tokens and the first bank.
     *
     * @param string $query
     * @return array
     */
    protected function marshalPartialQuery(string $query): array
    {
        $normalizedQuery = $this->marshalNodeKey($query);

        $allTokens = explode($this->getQueryDivider(), $normalizedQuery);
        $firstBank = explode($this->getQueryDivider(), $normalizedQuery, 1);

        return compact('allTokens', 'firstBank');
    }

    /**
     * ensures the validity of the provided value for the provided token.
     *
     * @param string $token
     * @param $value
     * @return mixed
     */
    protected function cover(string $token, $value)
    {
        if ( $value instanceof HiveInterface ) {
            return new HiveIdentity($this, $token);
        }

        return $value;
    }

    /**
     * marshals the current Path.
     *
     * @param string $token
     * @return string
     */
    protected function marshalCurrentPath(string $token = null): string
    {
        $container = $this;
        $stack = [$container->getName(), $token];

        while ( ! $container->getParent() ) {
            array_unshift($stack, $container->getName());
        }

        $path = join($this->getQueryDivider(), array_filter($stack));

        return empty($path) ? null : $path;
    }
}