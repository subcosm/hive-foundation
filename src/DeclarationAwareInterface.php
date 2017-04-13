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


interface DeclarationAwareInterface
{
    /**
     * issued after the declaration has been applied to a value.
     */
    const DECLARATION_STAGE = 'stage:declare';

    /**
     * declares the validation for the provided entity.
     *
     * @param string $entity
     * @param callable $callback
     * @return void
     */
    public function entity(string $entity, callable $callback): void;

    /**
     * declares the validation for all entities, when no specific validation was set.
     *
     * @param callable $callback
     */
    public function defaultEntity(callable $callback): void;
}
