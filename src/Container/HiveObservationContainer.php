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


use Subcosm\Hive\HiveObservationInterface;
use Subcosm\Observatory\AbstractObservationContainer;

class HiveObservationContainer extends AbstractObservationContainer implements HiveObservationInterface
{
    protected $contextualStageData;

    /**
     * sets the contextual stage data.
     *
     * @param array $data
     */
    public function withContextData(array $data)
    {
        $this->contextualStageData = $data;
    }

    /**
     * returns the contextual stage data.
     *
     * @return array
     */
    public function getContextData(): ? array
    {
        return $this->contextualStageData;
    }
}