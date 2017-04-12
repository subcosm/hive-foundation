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
     * declares the validation for the provided entity. The callback must return the intended value.
     *
     * @param string $entity
     * @param callable|null $callback
     * @throws HiveException on a root assignment attempt
     * @return void
     */
    public function declare(string $entity, callable $callback): void
    {
        $inspectionData = $this->marshalNodeQuery($entity);

        ['token' => $token, 'query' => $query, 'root' => $isRoot] = $inspectionData;

        if ( $isRoot ) {
            throw new HiveException('You can not assign declarations delegated to the root container');
        }

        if ( $query !== null ) {
            $targetNode = $this->node($query, true);

            if ( ! $targetNode instanceof DeclarationAwareInterface ) {
                throw new HiveException('Target not is not aware of declarations');
            }

            $targetNode->declare($token, $callback);
            return;
        }

        $this->declarations[$token] = $callback;
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
        if ( array_key_exists($token, $this->declarations) ) {
            $value = $this->callDeclarationCallback($this->declarations[$token], $value);

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
