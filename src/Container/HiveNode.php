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


use Closure;
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

        $this->withObservers($observers ?? new ObserverQueue());
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
        $query = $this->marshalNodeQuery((string) $entity);

        if ( $query->isEmpty() ) {
            throw new UnknownEntityException('Can not operate on empty queries');
        }

        if ( $query->callsRoot ) {
            return $this->getRoot()->get($query->rootlessQuery);
        }

        if ( $query->tokenCount > 1 && array_key_exists($query->firstToken, $this->nodes) ) {
            return $this->nodes[$query->firstToken]->get($query->query);
        }

        if ( $query->tokenCount > 1 && ! array_key_exists($query->firstToken, $this->nodes) ) {
            throw new UnknownEntityException('Unknown node: '.$query->firstToken);
        }

        if ( ! array_key_exists($query->firstToken, $this->values) ) {
            throw new UnknownEntityException('Unknown value key: '.$query->firstToken);
        }

        $resolvedValue = $this->values[$query->firstToken];

        if ( $resolvedValue instanceof Closure ) {
            $resolvedValue = $this->executeClosure($resolvedValue);
        }

        $this->update(static::GET_STAGE, function(HiveObservationContainer $container) use ($resolvedValue) {
            $container->withContextData([
                'value' => $resolvedValue,
            ]);
        });

        return $resolvedValue;
    }

    /**
     * Checks whether the provided entity is known or not.
     *
     * @param string $entity
     * @throws UnknownEntityException
     * @return bool
     */
    public function has($entity)
    {
        $query = $this->marshalNodeQuery((string) $entity);

        if ( $query->isEmpty() ) {
            throw new UnknownEntityException('Can not operate on empty queries');
        }

        if ( $query->callsRoot ) {
            return $this->getRoot()->has($query->rootlessQuery);
        }

        if ( $query->tokenCount > 2 ) {
            return $this->node($query->firstToken.$this->getQueryDivider().$query->segmentedQuery)->has($query->lastToken);
        }

        if ( $query->tokenCount > 1 ) {
            return $this->node($query->firstToken)->has($query->lastToken);
        }

        return array_key_exists($query->firstToken, $this->values);
    }

    /**
     * sets the value of the provided entity.
     *
     * @param string $entity
     * @param $value
     * @throws IncompatibleInstanceException when the value operates with HiveInterface and does not met requirements
     * @throws UnknownEntityException when the entity parameter is empty
     * @return mixed
     */
    public function set(string $entity, $value): void
    {
        $query = $this->marshalNodeQuery($entity);

        if ( $query->isEmpty() ) {
            throw new UnknownEntityException('Can not operate on empty queries');
        }

        if ( $query->callsRoot ) {
            $this->getRoot()->set($query->rootlessQuery, $value);

            return;
        }

        if ( $query->tokenCount > 2 ) {
            $this
                ->node($query->firstToken.$this->getQueryDivider().$query->segmentedQuery, true)
                ->set($query->lastToken, $value)
            ;

            return;
        }

        if ( $query->tokenCount > 1 ) {
            $this->node($query->firstToken, true )->set($query->lastToken, $value);

            return;
        }

        $this->values[$query->firstToken] = $this->cover($query->firstToken, $value);
    }

    /**
     * ensures the provided entity path, creates not existing nodes when $createIfNotExists is set to true.
     *
     * @param string $entity
     * @param bool $createIfNotExists
     * @throws UnknownEntityException on empty queries
     * @return null|HiveInterface
     */
    public function node(string $entity, bool $createIfNotExists = false): ? HiveInterface
    {
        $query = $this->marshalNodeQuery($entity);

        if ( $query->isEmpty() ) {
            throw new UnknownEntityException('Can not use empty names for nodes');
        }

        if ( $query->callsRoot ) {
            return $this->getRoot()->node($query->rootlessQuery);
        }

        if ( $createIfNotExists && ! array_key_exists($query->firstToken, $this->nodes) ) {
            $this->nodes[$query->firstToken] = $this->marshalNodeInstance($this, $query->firstToken);
        }

        if ( ! $createIfNotExists && ! array_key_exists($query->firstToken, $this->nodes) ) {
            return null;
        }

        return $query->query === null
            ? $this->nodes[$query->firstToken]
            : $this->nodes[$query->firstToken]->node($query->query, $createIfNotExists)
        ;
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

        while ( null !== $instance->getParent() ) {
            $instance = $instance->getParent();
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

        return $this;
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
     * wraps a closure into a closure to guarantee that a closure will be returned.
     *
     * @param Closure $closure
     * @return Closure
     */
    public function secure(Closure $closure): \Closure
    {
        return function() use ($closure) {
            return $closure;
        };
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
     * @return HiveQuery
     */
    protected function marshalNodeQuery(string $query): HiveQuery
    {
        $normalizedQuery = $this->marshalNodeKey($query);

        return new HiveQuery($normalizedQuery, $this->getQueryDivider(), $this->getRootIdentifier());
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
        $this->update(static::SET_STAGE, function(HiveObservationContainer $container) use ($token, $value) {
            $container->withContextData(compact('token', 'value'));
        });

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
        $stack = [$token];

        while ( null !== $container->getParent() ) {
            array_unshift($stack, $container->getName());
            $container = $container->getParent();
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

        return trim($key, $this->getQueryDivider().' ');
    }

    /**
     * marshals a new instance of the origin.
     *
     * @param HiveInterface $origin
     * @param string $token
     * @return HiveInterface
     */
    protected function marshalNodeInstance(HiveInterface $origin, string $token): HiveInterface
    {
        return new $origin(new HiveIdentity($origin, $token), $origin->getObservers());
    }

    /**
     * calls a closure.
     *
     * @param Closure $closure
     * @return mixed
     */
    protected function executeClosure(Closure $closure)
    {
        return call_user_func($closure);
    }
}
