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


use Psr\Container\ContainerInterface;
use Closure;
use Subcosm\Observatory\ObservableInterface;
use Subcosm\Observatory\ObserverQueue;

interface HiveInterface extends ContainerInterface, ObservableInterface
{
    /**
     * issued when a value had been successfully resolved.
     */
    const GET_STAGE = 'stage:get';

    /**
     * issued when a value had been successfully stored.
     */
    const SET_STAGE = 'stage:set';

    /**
     * issued when a node has been successfully established, will not issued when a node has been stored with set().
     */
    const NODE_STAGE = 'stage:node';

    /**
     * issued when a node has been stored with set(), will not issued when a node has been created with node().
     */
    const SET_NODE_STAGE = 'stage:set:node';

    /**
     * gets the queried entity.
     *
     * @param string $entity
     * @return mixed
     */
    public function get($entity);

    /**
     * Checks whether the provided entity is known or not.
     *
     * @param string $entity
     * @return bool
     */
    public function has($entity);

    /**
     * sets the value of the provided entity.
     *
     * @param string $entity
     * @param $value
     * @return mixed
     */
    public function set(string $entity, $value): void;

    /**
     * ensures the provided entity path, creates not existing nodes when $createIfNotExists is set to true.
     *
     * @param string $entity
     * @param bool $createIfNotExists
     * @return null|HiveInterface
     */
    public function node(string $entity, bool $createIfNotExists = false): ? HiveInterface;

    /**
     * checks if the current container is the root container. When $node is provided, the provided node
     * will be checked.
     *
     * @param HiveInterface|null $node
     * @return bool
     */
    public function isRoot(HiveInterface $node = null): bool;

    /**
     * returns the root node. When this node is the highest node in the hierarchy, the current
     * node will be returned.
     *
     * @return HiveInterface
     */
    public function getRoot(): HiveInterface;

    /**
     * checks whether this container has a parent node or not.
     *
     * @return bool
     */
    public function hasParent(): bool;

    /**
     * checks if the provided node is the parent node of this node.
     *
     * @param HiveInterface $node
     * @return bool
     */
    public function isParent(HiveInterface $node): bool;

    /**
     * returns the parent node or null if no parent node is given.
     *
     * @return null|HiveInterface
     */
    public function getParent(): ? HiveInterface;

    /**
     * returns the node dividing character for queries provided as entity keys.
     *
     * @return string
     */
    public function getQueryDivider(): string;

    /**
     * returns the root reference identifier for queries provided as entity keys.
     *
     * @return string
     */
    public function getRootIdentifier(): string;

    /**
     * returns the name of the node (or null when the current node is the root node).
     *
     * @return null|string
     */
    public function getName(): ? string;

    /**
     * marshals the current path.
     *
     * @return null|string
     */
    public function getPath(): ? string;

    /**
     * assigns a observer queue to the current container.
     *
     * @param ObserverQueue $observers
     * @return HiveInterface
     */
    public function withObservers(ObserverQueue $observers): HiveInterface;

    /**
     * returns the observers queue.
     *
     * @return ObserverQueue|null
     */
    public function getObservers(): ? ObserverQueue;

    /**
     * wraps a closure into a closure to guarantee that a closure will be returned.
     *
     * @param Closure $closure
     * @return Closure
     */
    public function secure(Closure $closure): \Closure;
}
