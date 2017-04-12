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


use Subcosm\Observatory\ObservationContainerInterface;

interface HiveObservationInterface extends ObservationContainerInterface
{
    /**
     * returns the contextual stage data.
     *
     * @return array
     */
    public function getContextData(): ? array;
}
