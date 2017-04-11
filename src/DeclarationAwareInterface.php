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
     * declares the validation for the provided entity.
     *
     * @param string $entity
     * @param callable $callback
     * @return void
     */
    public function declare(string $entity, callable $callback): void;
}