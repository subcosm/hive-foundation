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
use Subcosm\Observatory\ObserverInterface;
use Subcosm\Observatory\ObserverQueue;

class HiveNode implements HiveInterface
{
    /**
     * @var null|HiveInterface
     */
    protected $parent;

    /**
     * @var null|string
     */
    protected $name;

    /**
     * @var HiveInterface[]
     */
    protected $nodes = [];

    /**
     * @var mixed[]
     */
    protected $values = [];

    /**
     * @var ObserverQueue|null
     */
    protected $observers;

    /**
     * HiveNode constructor.
     * @param HiveIdentityInterface|null $parent
     * @param ObserverQueue $observers
     */
    public function __construct(HiveIdentityInterface $parent = null, ObserverQueue $observers = null)
    {
        $this->parent = $parent instanceof HiveIdentityInterface ? $parent->getParentNode() : null;
        $this->name = $parent instanceof HiveIdentityInterface ? $parent->getName() : null;
        $this->observers = $observers;
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
            $resolvedValue = $this->values[$token];

            $this->update(static::GET_STAGE, function(HiveObservationContainer $container) use ($resolvedValue) {
                $container->withContextData([
                    'value' => $resolvedValue,
                ]);
            });

            return $resolvedValue;
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
            return $this->nodes[$query] = $this->marshalNodeInstance($this)->node($forwardedQuery);
        }

        if ( ! array_key_exists($current, $this->nodes) && ! $createIfNotExists ) {
            return null;
        }

        return $this->nodes[$query]->node($forwardedQuery);
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
     * attaches an observer to the observable.
     *
     * @param ObserverInterface $observer
     * @return void
     */
    public function attach(ObserverInterface $observer): void
    {
        $this->observers->attach($observer);
    }

    /**
     * detaches an observer from the observable.
     *
     * @param ObserverInterface $observer
     * @return void
     */
    public function detach(ObserverInterface $observer): void
    {
        $this->observers->detach($observer);
    }

    /**
     * assigns a observer queue to the current container.
     *
     * @param ObserverQueue $observers
     * @return HiveInterface
     */
    public function withObservers(ObserverQueue $observers): HiveInterface
    {
        $this->observers = $observers;
    }

    /**
     * returns the observers queue.
     *
     * @return ObserverQueue|null
     */
    public function getObservers(): ? ObserverQueue
    {
        return $this->observers;
    }

    /**
     * updates the observers.
     *
     * @param string $stage
     * @param callable $callback
     */
    protected function update(string $stage, callable $callback)
    {
        if ( $this->observers instanceof ObserverQueue && count($this->observers) > 0 ) {
            call_user_func($callback, $container = new HiveObservationContainer($this, $stage));
            call_user_func($this->observers, $container);
        }
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
            $value->withObservers($this->getObservers());
        }

        $this->update(
            $value instanceof HiveInterface ? static::SET_NODE_STAGE : static::SET_STAGE,
            function(HiveObservationContainer $container) use ($token, $value)
            {
                $container->withContextData(compact('token', 'value'));
            }
        );

        return $value instanceof HiveInterface ? new HiveIdentity($value, $token) : $value;
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
        return new $origin($origin, $origin->getObservers());
    }
}
