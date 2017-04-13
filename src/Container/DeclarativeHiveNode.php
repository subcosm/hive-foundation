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


use Subcosm\Hive\DeclarationAwareInterface;
use Subcosm\Hive\Exception\HiveException;
use Subcosm\Hive\Exception\UnknownEntityException;
use Subcosm\Hive\HiveIdentityInterface;
use Subcosm\Hive\HiveInterface;
use Subcosm\Observatory\ObserverQueue;

/**
 * Class DeclarativeHiveNode
 * @package Subcosm\Hive\Container
 */
class DeclarativeHiveNode extends HiveNode implements DeclarationAwareInterface
{
    /**
     * @var array
     */
    protected $declarations = [];

    /**
     * @var callable|null
     */
    protected $defaultDeclaration;

    /**
     * HiveNode constructor.
     * @param HiveIdentityInterface|null $parent
     * @param ObserverQueue $observers
     */
    public function __construct(HiveIdentityInterface $parent = null, ObserverQueue $observers = null)
    {
        parent::__construct($parent, $observers);

        if ( $parent instanceof HiveIdentityInterface ) {
            $this->defaultDeclaration = $parent->getDefaultDeclaration();
        }
    }

    /**
     * declares the validation for the provided entity. The callback must return the intended value.
     *
     * @param string $entity
     * @param callable|null $callback
     * @throws HiveException on a root assignment attempt
     * @return void
     */
    public function entity(string $entity, callable $callback): void
    {
        $query = $this->marshalNodeQuery($entity);

        if ( $query->isEmpty() ) {
            throw new UnknownEntityException('Can not operate on empty queries');
        }

        if ( $query->callsRoot ) {
            $this->getRoot()->entity($query->rootlessQuery, $callback);

            return;
        }

        if ( $query->tokenCount > 2 ) {
            $this
                ->node($query->firstToken.$this->getQueryDivider().$query->segmentedQuery, true)
                ->entity($query->lastToken, $callback)
            ;

            return;
        }

        if ( $query->tokenCount > 1 ) {
            $this->node($query->firstToken, true)->entity($query->lastToken, $callback);

            return;
        }

        $this->declarations[$query->firstToken] = $callback;
    }

    /**
     * declares the validation for all entities, when no specific validation was set.
     *
     * @param callable $callback
     */
    public function defaultEntity(callable $callback): void
    {
        $this->defaultDeclaration = $callback;
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
        return new $origin(new HiveIdentity($origin, $token, $this->defaultDeclaration), $origin->getObservers());
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
        $declaration = $this->declarations[$token] ?? $this->defaultDeclaration ?? null;

        if ( is_callable($declaration) ) {
            $value = $this->callDeclarationCallback($declaration, $value);

            $this->update(static::DECLARATION_STAGE, function(HiveObservationContainer $container) use ($token, $value) {
                $container->withContextData(compact('token', 'value'));
            });
        }

        return parent::cover($token, $value);
    }

    /**
     * calls the declaration callback.
     *
     * @param callable $callback
     * @param $inbound
     * @return mixed
     */
    protected function callDeclarationCallback(callable $callback, $inbound)
    {
        return call_user_func($callback, $inbound);
    }
}
